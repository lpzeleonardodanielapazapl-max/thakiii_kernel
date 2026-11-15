package com.thakivoyage.service;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.util.*;
import jakarta.ws.rs.BadRequestException;

final class InventarioReglas {
    private InventarioReglas() {}
    static String texto(Map<String,Object> datos,String campo,int max,boolean obligatorio) {
        if(datos==null)throw new BadRequestException("Envía los datos de la operación.");
        Object valor=datos.get(campo);
        if(valor!=null && !(valor instanceof String)) throw new BadRequestException("Revisa el campo "+campo+".");
        String s=valor==null?"":valor.toString().strip();
        if((obligatorio && s.isBlank()) || s.length()>max) throw new BadRequestException("Completa correctamente "+campo+" (hasta "+max+" caracteres).");
        return s.isEmpty()?null:s;
    }
    static BigDecimal decimal(Object valor,boolean obligatorio,boolean positivo) {
        if(valor==null || valor.toString().isBlank()) {
            if(obligatorio) throw new BadRequestException("Indica la cantidad o el importe.");
            return null;
        }
        try {
            var n=new BigDecimal(valor.toString());
            if(n.scale()>2 || n.compareTo(new BigDecimal("9999999999.99"))>0 || (positivo?n.signum()<=0:n.signum()<0)) throw new IllegalArgumentException();
            return n;
        } catch(RuntimeException e) { throw new BadRequestException("Usa una cantidad o importe válido, con hasta dos decimales."); }
    }
    static LocalDate fecha(Map<String,Object> datos,String campo,boolean requerida,boolean futura) {
        String s=texto(datos,campo,10,requerida);
        if(s==null) return null;
        try {
            LocalDate d=LocalDate.parse(s);
            if(!futura && d.isAfter(LocalDate.now())) throw new IllegalArgumentException();
            return d;
        } catch(RuntimeException e) { throw new BadRequestException("Revisa la fecha: "+campo+"."); }
    }
    static boolean marcado(Object valor) { return Boolean.TRUE.equals(valor) || valor instanceof Number n && n.intValue()==1 || "1".equals(valor) || "true".equals(valor); }
    static String condicion(Map<String,Object> datos) {
        String s=texto(datos,"estadoFisico",20,true).toUpperCase(Locale.ROOT).replace("-","").replace(" ","");
        if(!Set.of("NUEVO","SEMINUEVO","USADO").contains(s)) throw new BadRequestException("Selecciona Nuevo, Seminuevo o Usado.");
        return s;
    }
    static Integer calificacion(Object valor,boolean requerida) {
        if(valor==null || valor.toString().isBlank()) {
            if(requerida) throw new BadRequestException("Evalúa el material con una calificación del 1 al 10.");
            return null;
        }
        try { int n=new BigDecimal(valor.toString()).intValueExact(); if(n<1 || n>10)throw new IllegalArgumentException();return n; }
        catch(RuntimeException e){throw new BadRequestException("La calificación debe estar entre 1 y 10.");}
    }
    static long id(Object valor) {
        try { long n=new BigDecimal(Objects.toString(valor,"")).longValueExact();if(n<1 || n>Integer.MAX_VALUE)throw new IllegalArgumentException();return n; }
        catch(RuntimeException e){throw new BadRequestException("Selecciona un registro válido.");}
    }
    static List<Map<String,Object>> renglones(Map<String,Object> datos) {
        if(!(datos.get("items") instanceof List<?> items) || items.isEmpty() || items.size()>100) throw new BadRequestException("Incluye entre 1 y 100 renglones.");
        List<Map<String,Object>> salida=new ArrayList<>();
        for(Object item:items) {
            if(!(item instanceof Map<?,?> mapa))throw new BadRequestException("Revisa los renglones.");
            Map<String,Object> fila=new LinkedHashMap<>();
            mapa.forEach((k,v)->{if(k instanceof String s)fila.put(s,v);});salida.add(fila);
        }
        return salida;
    }
    static String moneda(Map<String,Object> datos) {
        String moneda=texto(datos,"moneda",10,true);
        if(!Set.of("BOB","USD","EUR").contains(moneda))throw new BadRequestException("Selecciona una moneda válida.");return moneda;
    }
}
