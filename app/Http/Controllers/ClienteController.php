<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarClienteRequest;
use App\Models\Cliente;
use App\Services\ExportadorClientesExcel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClienteController extends Controller
{
    public function __construct(private readonly ExportadorClientesExcel $exportador) {}

    public function index(Request $request): View
    {
        $porPagina = (int) $request->input('por_pagina', 10);

        if (! in_array($porPagina, [10, 50, 100], true)) {
            $porPagina = 10;
        }

        $buscar = trim((string) $request->input('buscar', ''));

        $clientes = $this->clientesFiltrados($request, $buscar)
            ->orderBy('nombre')
            ->paginate($porPagina)
            ->withQueryString();

        return view('clientes.index', [
            'clientes' => $clientes,
            'porPagina' => $porPagina,
            'buscar' => $buscar,
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $clientes = $this->clientesFiltrados($request, $buscar)->orderBy('nombre')->get();

        $spreadsheet = $this->exportador->generar($clientes);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'clientes.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function clientesFiltrados(Request $request, string $buscar): Builder
    {
        return Cliente::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('cuit', 'like', "%{$buscar}%")
                        ->orWhere('telefono', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%");
                });
            });
    }

    public function create(): View
    {
        return view('clientes.create');
    }

    public function store(GuardarClienteRequest $request): RedirectResponse
    {
        Cliente::create([
            ...$request->validated(),
            'empresa_id' => $request->user()->empresa_id,
            'activo' => true,
        ]);

        return redirect()->route('clientes.index')->with('status', 'Cliente creado.');
    }

    public function edit(Request $request, Cliente $cliente): View
    {
        $this->autorizar($request, $cliente);

        return view('clientes.edit', ['cliente' => $cliente]);
    }

    public function update(GuardarClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $this->autorizar($request, $cliente);

        $cliente->update($request->validated());

        return redirect()->route('clientes.index')->with('status', 'Cliente actualizado.');
    }

    public function activar(Request $request, Cliente $cliente): RedirectResponse
    {
        $this->autorizar($request, $cliente);

        $cliente->update(['activo' => true]);

        return back()->with('status', 'Cliente activado.');
    }

    public function desactivar(Request $request, Cliente $cliente): RedirectResponse
    {
        $this->autorizar($request, $cliente);

        $cliente->update(['activo' => false]);

        return back()->with('status', 'Cliente desactivado.');
    }

    private function autorizar(Request $request, Cliente $cliente): void
    {
        if ($cliente->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }
}
