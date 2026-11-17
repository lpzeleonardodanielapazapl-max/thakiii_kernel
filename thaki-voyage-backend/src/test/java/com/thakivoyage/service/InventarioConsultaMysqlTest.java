package com.thakivoyage.service;

import static org.junit.jupiter.api.Assertions.*;
import static org.junit.jupiter.api.Assumptions.assumeTrue;
import java.sql.*;
import java.util.*;
import java.util.regex.Pattern;
import org.junit.jupiter.api.Test;

/** Comprueba el esquema original exclusivamente con consultas SELECT. */
class InventarioConsultaMysqlTest {
    static class SoloLectura extends PortalQuery {
        final Connection c;
        SoloLectura(Connection c){this.c=c;}
        @Override public List<Map<String,Object>> lista(String sql,Map<String,?> params,String... columnas){
            if(!sql.stripLeading().startsWith("SELECT"))throw new IllegalArgumentException("Solo SELECT.");
            var nombres=new ArrayList<String>();var m=Pattern.compile(":([A-Za-z][A-Za-z0-9_]*)").matcher(sql);var out=new StringBuilder();
            while(m.find()){nombres.add(m.group(1));m.appendReplacement(out,"?");}m.appendTail(out);
            try(var st=c.prepareStatement(out.toString())){
                for(int i=0;i<nombres.size();i++)st.setObject(i+1,params.get(nombres.get(i)));
                try(var rs=st.executeQuery()){
                    List<Map<String,Object>> filas=new ArrayList<>();while(rs.next()){
                        Map<String,Object> fila=new LinkedHashMap<>();for(int i=0;i<columnas.length;i++){Object v=rs.getObject(i+1);if(v instanceof java.sql.Date || v instanceof Timestamp)v=v.toString();fila.put(columnas[i],v);}filas.add(fila);
                    }return filas;
                }
            }catch(SQLException e){throw new IllegalStateException(sql,e);}
        }
        @Override public int ejecutar(String sql,Map<String,?> params){throw new UnsupportedOperationException("Sin escrituras.");}
    }
    @Test void consultasYAbcFuncionanConElEsquemaOriginal() throws Exception {
        String url=System.getenv("THAKI_INVENTARIO_READ_URL");assumeTrue(url!=null && !url.isBlank(),"Configura la conexión de lectura para MySQL.");
        try(var c=DriverManager.getConnection(url,System.getenv("THAKI_INVENTARIO_READ_USER"),System.getenv("THAKI_INVENTARIO_READ_PASSWORD"))){
            c.setReadOnly(true);var db=new SoloLectura(c);var service=new InventarioConsultaService();service.db=db;
            assertTrue(service.resumen().containsKey("unidades"));
            for(var d:service.documentos())assertNotNull(service.documento(d.get("codigo").toString()).get("items"));
            var abc=new AbcService();abc.db=db;assertNotNull(abc.calcular());
        }
    }
}
