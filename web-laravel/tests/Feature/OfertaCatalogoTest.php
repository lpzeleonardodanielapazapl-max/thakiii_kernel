<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OfertaCatalogoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function hoteles(): array
    {
        return [[
            'codigo' => 'HOT-UYU', 'nombre' => 'Hotel Luna Salada', 'descripcion' => 'Junto al salar.',
            'categoria' => 4, 'ciudad' => 'Uyuni', 'direccion' => 'Salar', 'servicios' => 'Wifi',
            'checkIn' => '14:00', 'checkOut' => '10:00',
            'habitaciones' => [[
                'codigo' => 'HAB-UYU-DBL', 'nombre' => 'Doble', 'descripcion' => 'Cama matrimonial.',
                'capacidad' => 2, 'camas' => 1, 'tipoCamas' => 'Matrimonial',
                'precioNoche' => 350, 'moneda' => 'BOB', 'desayuno' => 1,
            ]],
        ]];
    }

    public function test_el_cliente_ve_habitacion_precio_y_puede_elegirla(): void
    {
        Http::fake(['*/api/publico/hospedaje' => Http::response($this->hoteles())]);
        $this->get('/alojamientos')->assertOk()->assertSeeText('Hotel Luna Salada')->assertSeeText('350 BOB')->assertSeeText('Elegir');

        $usuario = ['id' => 7, 'rol' => 'CLIENTE', 'nombre' => 'Ana'];
        Http::fake([
            '*/api/publico/hospedaje' => Http::response($this->hoteles()),
            '*/api/acceso/sesion' => Http::response($usuario),
            '*/api/cliente/perfil' => Http::response(['completitud' => ['completo' => true]]),
        ]);
        $this->withSession(['api_token' => 'token', 'usuario' => $usuario])->post('/alojamientos/elegir', ['hotel' => 'HOT-UYU', 'habitacion' => 'HAB-UYU-DBL'])
            ->assertRedirect(route('portal.solicitud'))
            ->assertSessionHas('viaje_borrador.hotel', 'Hotel Luna Salada, Uyuni · Doble · 350 BOB/noche · desayuno');
        $this->get('/mi-viaje/solicitar')->assertOk()->assertSeeText('Lo que ya elegiste')->assertSeeText('Hotel Luna Salada, Uyuni · Doble · 350 BOB/noche · desayuno');
    }

    public function test_un_visitante_conserva_la_eleccion_hasta_crear_su_cuenta(): void
    {
        Http::fake(['*/api/publico/transporte' => Http::response(['servicios' => [[
            'codigo' => 'TRS-AER', 'nombre' => 'Aeropuerto a tu hotel en La Paz', 'descripcion' => 'Recogida en El Alto.',
            'tipo' => 'Privado', 'origen' => 'Aeropuerto El Alto', 'destino' => 'Hotel en La Paz',
            'distanciaKm' => 12, 'duracionMin' => 45, 'precio' => 80, 'moneda' => 'BOB', 'capacidad' => 4,
        ]], 'vehiculos' => [['codigo' => 'VEH-002', 'tipo' => 'Vagoneta', 'marca' => 'Toyota', 'modelo' => 'Land Cruiser', 'capacidad' => 4]]])]);
        $this->get('/traslados')->assertOk()->assertSeeText('Vagoneta')->assertSeeText('80 BOB');
        $this->post('/traslados/elegir', ['servicio' => 'TRS-AER'])->assertRedirect(route('acceso.registro'))
            ->assertSessionHas('viaje_borrador.transporteCodigo', 'TRS-AER');
    }

    public function test_el_personal_no_guarda_una_eleccion_de_cliente(): void
    {
        Http::fake(['*/api/publico/hospedaje' => Http::response($this->hoteles())]);
        $this->withSession(['usuario' => ['rol' => 'ADMIN']])->from('/alojamientos')->post('/alojamientos/elegir', ['hotel' => 'HOT-UYU', 'habitacion' => 'HAB-UYU-DBL'])
            ->assertRedirect('/alojamientos')->assertSessionHasErrors('oferta')->assertSessionMissing('viaje_borrador');
    }

    public function test_personal_consulta_catalogos_internos_en_lugar_del_panel_del_viajero(): void
    {
        Http::preventStrayRequests();
        foreach (['ADMIN', 'ADMINISTRATIVO'] as $rol) {
            $this->withSession(['usuario' => ['rol' => $rol]])->get('/alojamientos')->assertRedirect(route('hoteles.index'));
            $this->get('/traslados')->assertRedirect(route('servicios-transporte.index'));
            $this->post('/viaje/eleccion/quitar', ['tipo' => 'hotel'])->assertSessionHasErrors('oferta')->assertSessionMissing('viaje_borrador');
        }
        Http::assertNothingSent();
    }

    public function test_una_habitacion_desconocida_no_se_guarda(): void
    {
        Http::fake(['*/api/publico/hospedaje' => Http::response($this->hoteles())]);
        $this->from('/alojamientos')->post('/alojamientos/elegir', ['hotel' => 'HOT-UYU', 'habitacion' => 'HAB-NO'])
            ->assertRedirect('/alojamientos')->assertSessionHasErrors('oferta')->assertSessionMissing('viaje_borrador.hotelCodigo');
    }
}
