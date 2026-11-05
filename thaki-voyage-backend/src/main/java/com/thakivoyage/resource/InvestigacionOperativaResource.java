package com.thakivoyage.resource;

import jakarta.ws.rs.BadRequestException;
import jakarta.ws.rs.Consumes;
import jakarta.ws.rs.POST;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;

@Path("/api/io")
@Consumes(MediaType.APPLICATION_JSON)
@Produces(MediaType.APPLICATION_JSON)
public class InvestigacionOperativaResource {

    public record EoqRequest(
        double demandaAnual,
        double costoPedido,
        double costoMantenimientoAnual,
        double costoUnitario,
        double plazoEntregaDias,
        double diasAnuales
    ) {}

    public record EoqResultado(
        double loteTeorico,
        long loteRecomendado,
        double pedidosAnuales,
        double diasEntrePedidos,
        double demandaDiaria,
        double puntoReorden,
        double costoCompraAnual,
        double costoOrdenarAnual,
        double costoMantenerAnual,
        double costoGestionAnual,
        double costoTotalAnual
    ) {}

    @POST
    @Path("/eoq")
    public Response calcular(EoqRequest datos) {

        if (datos == null) {
            throw new BadRequestException("Debes enviar los parámetros.");
        }

        double d = datos.demandaAnual();
        double s = datos.costoPedido();
        double h = datos.costoMantenimientoAnual();
        double c = datos.costoUnitario();
        double l = datos.plazoEntregaDias();
        double dias = datos.diasAnuales();

        double[] valores = {d, s, h, c, l, dias};

        for (double valor : valores) {
            if (!Double.isFinite(valor)) {
                throw new BadRequestException("Los valores deben ser finitos.");
            }
        }

        if (d <= 0 || s <= 0 || h <= 0 || dias <= 0 || c < 0 || l < 0) {
            throw new BadRequestException(
                "Demanda, costo de pedido, mantenimiento y días deben ser " +
                "mayores que cero. Precio y plazo no pueden ser negativos."
            );
        }

        double q = Math.sqrt(2.0 * d * s / h);

        // Comparamos los enteros vecinos; no redondeamos siempre hacia arriba.
        long inferior = Math.max(1L, (long) Math.floor(q));
        long superior = Math.max(1L, (long) Math.ceil(q));

        double costoInferior = d / inferior * s + inferior / 2.0 * h;
        double costoSuperior = d / superior * s + superior / 2.0 * h;

        long recomendado = costoInferior <= costoSuperior
            ? inferior : superior;

        double compra = d * c;
        double ordenar = d / recomendado * s;
        double mantener = recomendado / 2.0 * h;

        EoqResultado resultado = new EoqResultado(
            q,
            recomendado,
            d / recomendado,
            dias * recomendado / d,
            d / dias,
            d / dias * l,
            compra,
            ordenar,
            mantener,
            ordenar + mantener,
            compra + ordenar + mantener
        );

        return Response.ok(resultado).build();
    }
}