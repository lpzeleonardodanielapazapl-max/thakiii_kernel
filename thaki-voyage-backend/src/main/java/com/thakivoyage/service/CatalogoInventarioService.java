package com.thakivoyage.service;

import java.util.*;
import static com.thakivoyage.service.InventarioReglas.*;
import static com.thakivoyage.service.PortalQuery.valores;
import jakarta.enterprise.context.ApplicationScoped;
import jakarta.inject.Inject;
import jakarta.transaction.Transactional;
import jakarta.ws.rs.*;

@ApplicationScoped
public class CatalogoInventarioService {
    @Inject PortalQuery db;
    private record Catalogo(String tabla,String codigo,String nombre,int longitud){}
    private Catalogo tipo(String tipo){return switch(tipo){
        case "almacenes" -> new Catalogo("almacen","cod_almacen","nombre",100);
        case "clases" -> new Catalogo("clase_producto","cod_clase","des_clase",150);
        case "marcas" -> new Catalogo("marca","cod_mar","des_mar",100);
        case "medidas" -> new Catalogo("unidad_medida","cod_um","des_um",50);
        default -> throw new NotFoundException();
    };}
    public Map<String,Object> listar(){return valores(
        "almacenes",db.lista("SELECT cod_almacen,nombre,ubicacion,estado FROM almacen ORDER BY nombre",Map.of(),"codigo","nombre","ubicacion","estado"),
        "clases",db.lista("SELECT cod_clase,des_clase,tipo_inv,es_serializado,es_consumible FROM clase_producto ORDER BY des_clase",Map.of(),"codigo","nombre","categoria","serializado","consumible"),
        "marcas",db.lista("SELECT cod_mar,des_mar,pais_origen FROM marca ORDER BY des_mar",Map.of(),"codigo","nombre","pais"),
        "medidas",db.lista("SELECT cod_um,des_um,abreviatura FROM unidad_medida ORDER BY des_um",Map.of(),"codigo","nombre","abreviatura"));}
    @Transactional
    public Map<String,String> guardar(AccesoService.Identidad user,String tipo,String codigo,Map<String,Object> datos){
        if(!Set.of("ADMIN","ADMINISTRATIVO").contains(user.rol()))throw new ForbiddenException();
        var c=tipo(tipo);String nombre=texto(datos,"nombre",c.longitud,true);boolean nuevo=codigo==null;
        if(nuevo)codigo=(tipo.equals("medidas")?"UM":"CAT")+UUID.randomUUID().toString().replace("-","").substring(0,tipo.equals("medidas")?8:16);
        else if(db.lista("SELECT "+c.codigo+" FROM "+c.tabla+" WHERE "+c.codigo+"=:codigo FOR UPDATE",Map.of("codigo",codigo),"codigo").isEmpty())throw new NotFoundException();
        if(!db.lista("SELECT "+c.codigo+" FROM "+c.tabla+" WHERE "+c.nombre+"=:nombre AND "+c.codigo+"<>:codigo",Map.of("nombre",nombre,"codigo",codigo),"codigo").isEmpty())throw new ClientErrorException("Ya existe un registro con ese nombre.",409);
        String extras="";Map<String,Object> params=valores("codigo",codigo,"nombre",nombre);
        if(tipo.equals("almacenes")){
            String estado=texto(datos,"estado",20,true);if(!Set.of("ACTIVO","INACTIVO").contains(estado))throw new BadRequestException("Estado inválido.");
            extras=",ubicacion=:ubicacion,estado=:estado";params.put("ubicacion",texto(datos,"ubicacion",200,false));params.put("estado",estado);
        } else if(tipo.equals("clases")){
            String cat=texto(datos,"categoria",30,true);if(!Set.of("ALPINISMO","COCINA","EVENTOS").contains(cat))throw new BadRequestException("Categoría inválida.");
            boolean serializado=marcado(datos.get("serializado")),consumible=marcado(datos.get("consumible"));
            if(serializado && consumible)throw new BadRequestException("Los consumibles se controlan por cantidad.");
            if(!nuevo){
                var actual=db.primero("SELECT es_serializado,es_consumible FROM clase_producto WHERE cod_clase=:codigo",Map.of("codigo",codigo),"serializado","consumible");
                if((serializado!=marcado(actual.get("serializado")) || consumible!=marcado(actual.get("consumible"))) && !db.lista("SELECT cod_pro FROM producto WHERE cod_clase=:codigo",Map.of("codigo",codigo),"codigo").isEmpty())throw new ClientErrorException("No cambies el modo de control de una clase que ya tiene productos. Crea una clase distinta.",409);
            }
            extras=",tipo_inv=:categoria,es_serializado=:serializado,es_consumible=:consumible";params.put("categoria",cat);params.put("serializado",serializado?1:0);params.put("consumible",consumible?1:0);
        } else if(tipo.equals("marcas")){extras=",pais_origen=:pais";params.put("pais",texto(datos,"pais",80,false));}
        else {extras=",abreviatura=:abreviatura";params.put("abreviatura",texto(datos,"abreviatura",10,false));}
        if(nuevo)db.ejecutar("INSERT INTO "+c.tabla+" ("+c.codigo+","+c.nombre+") VALUES (:codigo,:nombre)",Map.of("codigo",codigo,"nombre",nombre));
        db.ejecutar("UPDATE "+c.tabla+" SET "+c.nombre+"=:nombre"+extras+" WHERE "+c.codigo+"=:codigo",params);
        db.ejecutar("INSERT INTO auditoria (tabla,registro_id,accion,id_usuario,datos_despues) VALUES (:tabla,:codigo,:accion,:usuario,JSON_OBJECT('nombre',:nombre))",Map.of("tabla",c.tabla,"codigo",codigo,"accion",nuevo?"CREAR":"ACTUALIZAR","usuario",user.id(),"nombre",nombre));
        return Map.of("codigo",codigo);
    }
}
