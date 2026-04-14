@extends('layouts.app')

@section('title')
    Notificaciones — Dispositivos
@endsection

@section('content')
    <div class="page-title">
        <div class="title_left">
            <h3>Dispositivos</h3>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="col-sm-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Listado</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Platform</th>
                                <th>Device ID</th>
                                <th>Token (preview)</th>
                                <th>Activo</th>
                                <th>Creado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tokens as $t)
                                @php
                                    $u = $t->usuario;
                                    $email = $u ? $u->email : ('#' . $t->user_id);
                                    $tok = (string) $t->token;
                                    $preview = strlen($tok) > 36 ? substr($tok, 0, 36) . '…' : $tok;
                                @endphp
                                <tr>
                                    <td>{{ $email }}</td>
                                    <td>{{ $t->platform }}</td>
                                    <td>{{ $t->device_id }}</td>
                                    <td><code>{{ $preview }}</code></td>
                                    <td>{{ !empty($t->active) ? 'Sí' : 'No' }}</td>
                                    <td>{{ $t->created_at ? $t->created_at->format('Y-m-d H:i') : '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">No hay registros.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if (method_exists($tokens, 'links'))
                    <div class="text-center">{{ $tokens->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
