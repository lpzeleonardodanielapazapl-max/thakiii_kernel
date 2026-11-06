@if(session('exito'))<div class="aviso exito" role="status">{{ session('exito') }}</div>@endif
@if($errors->any())<div class="aviso error" role="alert"><strong>Revisa lo siguiente:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
