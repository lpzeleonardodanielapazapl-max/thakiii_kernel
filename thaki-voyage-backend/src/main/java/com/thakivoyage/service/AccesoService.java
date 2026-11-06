package com.thakivoyage.service;

import static com.thakivoyage.service.PortalQuery.valores;
import java.security.*;
import java.time.LocalDateTime;
import java.util.*;
import java.util.concurrent.ConcurrentHashMap;
import com.thakivoyage.entity.Usuario;
import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.transaction.Transactional;
import jakarta.ws.rs.*;

@ApplicationScoped
public class AccesoService {
    @Inject PortalQuery db;
    @Inject PasswordService passwords;
    @Inject PortalService portal;
    public record Credenciales(String email, String password) {}
    public record Registro(String nombres, String apellidos, String email, String password) {}
    public record Identidad(long id, String email, String nombre, String rol) {}
    public record Sesion(String token, Identidad usuario) {}
    private record Intentos(long minuto, int cantidad) {}
    private final ConcurrentHashMap<String, Intentos> intentos = new ConcurrentHashMap<>();

    private void limitar(String correo) {
        long minuto = System.currentTimeMillis() / 60000;
        if (intentos.size() > 5000) intentos.entrySet().removeIf(e -> e.getValue().minuto() < minuto);
        var result = intentos.compute(digest(correo), (k, v) -> v == null || v.minuto() < minuto ? new Intentos(minuto, 1) : new Intentos(minuto, v.cantidad() + 1));
        if (result.cantidad() > 8) throw new ClientErrorException("Demasiados intentos. Espera un minuto e inténtalo nuevamente.", 429);
    }

    public static String texto(String value, String label, int max) {
        if (value == null || value.isBlank() || value.strip().length() > max) throw new BadRequestException("Revisa " + label + ".");
        return value.strip();
    }

    public static String correo(String value) {
        String email = texto(value, "el correo", 150).toLowerCase(Locale.ROOT);
        if (!email.matches("^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$")) throw new BadRequestException("Introduce un correo válido.");
        return email;
    }

    private void clave(String value) {
        if (value == null || value.length() < 8 || value.length() > 255 || value.isBlank()) throw new BadRequestException("La contraseña debe tener entre 8 y 255 caracteres.");
    }

    @Transactional
    public Sesion login(Credenciales datos) {
        if (datos == null) throw new BadRequestException("Introduce tus credenciales.");
        String email = correo(datos.email());
        limitar(email);
        var users = db.usuariosPorCorreo(email);
        Usuario user = users.isEmpty() ? null : users.getFirst();
        String password = datos.password() == null ? "" : datos.password();
        if (password.length() > 255) throw new NotAuthorizedException("Correo o contraseña incorrectos.", jakarta.ws.rs.core.Response.status(401).header("WWW-Authenticate", "Bearer").build());
        if (user == null) passwords.hash("verificacion-sin-cuenta");
        if (user == null || !Boolean.TRUE.equals(user.activo) || !passwords.matches(password, user.passwordHash)) throw new NotAuthorizedException("Correo o contraseña incorrectos.", jakarta.ws.rs.core.Response.status(401).header("WWW-Authenticate", "Bearer").build());
        intentos.remove(digest(email));
        user.ultimoAcceso = LocalDateTime.now();
        return sesion(user);
    }

    @Transactional
    public Sesion registrar(Registro datos) {
        if (datos == null) throw new BadRequestException("Completa tus datos.");
        String nombres = texto(datos.nombres(), "los nombres", 100);
        String apellidos = texto(datos.apellidos(), "los apellidos", 100);
        String email = correo(datos.email());
        clave(datos.password());
        limitar(email);
        var rol = db.primero("SELECT cod_rol FROM rol WHERE des_rol = 'CLIENTE' FOR UPDATE", Map.of(), "codigo");
        if (rol.isEmpty()) throw new ClientErrorException("El registro no está disponible. Contacta al personal.", 409);
        if (!db.lista("SELECT id_usuario FROM usuario WHERE email = :email", Map.of("email", email), "id").isEmpty()
            || !db.lista("SELECT cod_persona FROM persona WHERE email = :email", Map.of("email", email), "codigo").isEmpty()) {
            throw new ClientErrorException("Ese correo ya está registrado. Ingresa con tu cuenta o solicita ayuda al personal.", 409);
        }
        Usuario user = crear(email, "viajero-" + UUID.randomUUID().toString().substring(0, 12), datos.password(), (String) rol.get("codigo"));
        String persona = GestionValidationService.codigo("PER");
        db.ejecutar("INSERT INTO persona (cod_persona, nombres, apellidos, email) VALUES (:codigo, :nombres, :apellidos, :email)", valores("codigo", persona, "nombres", nombres, "apellidos", apellidos, "email", email));
        db.ejecutar("INSERT INTO cliente (cod_cliente, cod_persona, estado) VALUES (:codigo, :persona, 'ACTIVO')", Map.of("codigo", GestionValidationService.codigo("CLI"), "persona", persona));
        return sesion(user);
    }

    public boolean necesitaAdministrador() {
        return db.lista("SELECT u.id_usuario FROM usuario u JOIN rol r ON r.cod_rol=u.cod_rol WHERE r.des_rol='ADMIN' LIMIT 1", Map.of(), "id").isEmpty();
    }

    public void protegerAdministrador(long id,String nuevoRol,boolean activo,boolean eliminar) {
        var rol=db.primero("SELECT cod_rol FROM rol WHERE des_rol='ADMIN' FOR UPDATE",Map.of(),"codigo");
        if(rol.isEmpty())return;
        var actual=db.primero("SELECT cod_rol,activo FROM usuario WHERE id_usuario=:id",Map.of("id",id),"rol","activo");
        if(!Objects.equals(actual.get("rol"),rol.get("codigo")))return;
        if(!eliminar && activo && Objects.equals(nuevoRol,rol.get("codigo")))return;
        if(db.lista("SELECT id_usuario FROM usuario WHERE cod_rol=:rol AND activo=1 AND id_usuario<>:id LIMIT 1",Map.of("rol",rol.get("codigo"),"id",id),"id").isEmpty())throw new ClientErrorException("Conserva al menos un administrador activo. Crea o activa otro ADMIN antes de cambiar este acceso.",409);
    }

    @Transactional
    public Sesion inicializar(Registro datos) {
        if (datos == null) throw new BadRequestException("Completa los datos de administración.");
        var rol = db.primero("SELECT cod_rol FROM rol WHERE des_rol='ADMIN' FOR UPDATE", Map.of(), "codigo");
        if (rol.isEmpty() || !necesitaAdministrador()) throw new ClientErrorException("La administración ya está configurada.", 409);
        String email = correo(datos.email());
        clave(datos.password());
        String nombre = texto(datos.nombres(), "el nombre", 60);
        if (!db.lista("SELECT id_usuario FROM usuario WHERE email=:email", Map.of("email", email), "id").isEmpty()) throw new ClientErrorException("Ese correo ya tiene una cuenta. Utiliza otro correo para el administrador inicial.", 409);
        return sesion(crear(email, nombre, datos.password(), (String) rol.get("codigo")));
    }

    private Usuario crear(String email, String nombre, String password, String rol) {
        Usuario user = new Usuario();
        user.email = email; user.username = nombre; user.passwordHash = passwords.hash(password);
        user.codRol = rol; user.activo = true; user.emailVerificado = false; user.createdAt = LocalDateTime.now();
        db.persistirUsuario(user);
        return user;
    }

    private Sesion sesion(Usuario user) {
        String rol = (String) db.primero("SELECT des_rol FROM rol WHERE cod_rol=:codigo", Map.of("codigo", user.codRol), "rol").get("rol");
        if (!Set.of("ADMIN", "ADMINISTRATIVO", "CLIENTE").contains(rol == null ? "" : rol)) throw new ForbiddenException("La cuenta no tiene un rol habilitado.");
        byte[] bytes = new byte[32]; new SecureRandom().nextBytes(bytes);
        String token = Base64.getUrlEncoder().withoutPadding().encodeToString(bytes);
        db.ejecutar("INSERT INTO sesion (id_sesion, id_usuario, fecha_inicio, activa) VALUES (:token, :usuario, CURRENT_TIMESTAMP, 1)", Map.of("token", digest(token), "usuario", user.idUsuario));
        var identity = new Identidad(user.idUsuario, user.email, user.username == null ? user.email : user.username, rol);
        if (rol.equals("CLIENTE")) portal.asignar(identity);
        return new Sesion(token, identity);
    }

    public Identidad autenticar(String authorization) {
        if (authorization == null || !authorization.startsWith("Bearer ")) throw new NotAuthorizedException("Inicia sesión para continuar.", jakarta.ws.rs.core.Response.status(401).header("WWW-Authenticate", "Bearer").build());
        String token = authorization.substring(7);
        if (!token.matches("[A-Za-z0-9_-]{43}")) throw new NotAuthorizedException("La sesión no es válida.", jakarta.ws.rs.core.Response.status(401).header("WWW-Authenticate", "Bearer").build());
        var user = db.primero("""
            SELECT u.id_usuario, u.email, COALESCE(u.username,u.email), r.des_rol
            FROM sesion s JOIN usuario u ON u.id_usuario=s.id_usuario JOIN rol r ON r.cod_rol=u.cod_rol
            WHERE s.id_sesion=:token AND s.activa=1 AND s.fecha_fin IS NULL AND u.activo=1
              AND s.fecha_inicio > CURRENT_TIMESTAMP - INTERVAL 8 HOUR
            """, Map.of("token", digest(token)), "id", "email", "nombre", "rol");
        if (user.isEmpty()) throw new NotAuthorizedException("La sesión terminó. Vuelve a ingresar.", jakarta.ws.rs.core.Response.status(401).header("WWW-Authenticate", "Bearer").build());
        if (!Set.of("ADMIN", "ADMINISTRATIVO", "CLIENTE").contains((String) user.get("rol"))) throw new ForbiddenException("La cuenta no tiene un rol habilitado.");
        return new Identidad(((Number) user.get("id")).longValue(), (String) user.get("email"), (String) user.get("nombre"), (String) user.get("rol"));
    }

    @Transactional
    public void salir(String authorization) {
        autenticar(authorization);
        db.ejecutar("UPDATE sesion SET activa=0, fecha_fin=CURRENT_TIMESTAMP WHERE id_sesion=:token", Map.of("token", digest(authorization.substring(7))));
    }

    public static String digest(String value) {
        try { return HexFormat.of().formatHex(MessageDigest.getInstance("SHA-256").digest(value.getBytes(java.nio.charset.StandardCharsets.UTF_8))); }
        catch (NoSuchAlgorithmException e) { throw new IllegalStateException(e); }
    }
}
