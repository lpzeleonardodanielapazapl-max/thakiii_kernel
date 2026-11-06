@props(['index', 'dia' => []])
<fieldset class="editor-dia" data-dia>
    <legend data-dia-titulo>Día {{ is_numeric($index) ? $index + 1 : '' }}</legend>
    <div class="editor-dia-cabecera"><p>Describe el recorrido y los servicios de este día.</p><div class="dia-acciones" hidden data-dia-controles><button type="button" class="dia-control" data-dia-accion="subir">↑ Subir</button><button type="button" class="dia-control" data-dia-accion="bajar">↓ Bajar</button><button type="button" class="dia-control" data-dia-accion="quitar">Quitar día</button></div></div>
    <div class="editor-dia-campos">
        <div><label for="dia-{{ $index }}-descripcion" data-dia-label="descripcion">Actividades y recorrido *</label><textarea id="dia-{{ $index }}-descripcion" name="dias[{{ $index }}][descripcion]" data-dia-campo="descripcion" rows="4" maxlength="4000" required @if($errors->has('dias.'.$index.'.descripcion')) aria-invalid="true" aria-describedby="error-dias-{{ $index }}-descripcion" @endif>{{ $dia['descripcion'] ?? '' }}</textarea><x-error-campo :name="'dias.'.$index.'.descripcion'"/></div>
        <div><label for="dia-{{ $index }}-servicios" data-dia-label="servicios">Servicios incluidos (opcional)</label><textarea id="dia-{{ $index }}-servicios" name="dias[{{ $index }}][servicios]" data-dia-campo="servicios" rows="2" maxlength="2000" placeholder="Por ejemplo: traslado, alojamiento y desayuno" @if($errors->has('dias.'.$index.'.servicios')) aria-invalid="true" aria-describedby="error-dias-{{ $index }}-servicios" @endif>{{ $dia['servicios'] ?? '' }}</textarea><x-error-campo :name="'dias.'.$index.'.servicios'"/></div>
    </div>
</fieldset>
