<?php

namespace App\Http\Controllers;

use App\Models\B2BPort;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class B2BPortController extends Controller
{
    public function adminIndex()
    {
        $ports = B2BPort::query()->latest()->paginate(20);

        if (request()->expectsJson()) {
            return response()->json(['ports' => $ports]);
        }

        return view('backend.b2b.ports.index', compact('ports'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $port = B2BPort::create((new B2BPort())->filterPersistable($data));

        if (!request()->expectsJson()) {
            flash(translate('Port created successfully.'))->success();
            return back();
        }

        return response()->json(['success' => true, 'port_id' => $port->id]);
    }

    public function update(Request $request, $id)
    {
        $port = B2BPort::findOrFail($id);
        $port->update($port->filterPersistable($this->validatedData($request, $port)));

        flash(translate('Port updated successfully.'))->success();

        return back();
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'b2b-ports-' . now()->format('YmdHis') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['name', 'country', 'city', 'unlocode', 'code', 'port_type', 'latitude', 'longitude', 'timezone', 'is_active']);

            B2BPort::query()->orderBy('name')->each(function ($port) use ($handle) {
                fputcsv($handle, [
                    $port->name,
                    $port->country,
                    $port->city,
                    $port->unlocode,
                    $port->code,
                    $port->port_type,
                    $port->latitude,
                    $port->longitude,
                    $port->timezone,
                    $port->is_active ? 1 : 0,
                ]);
            });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        $headers = fgetcsv($handle) ?: [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            if (!$data || blank($data['code'] ?? null)) {
                continue;
            }

            B2BPort::updateOrCreate(
                ['code' => $data['code']],
                (new B2BPort())->filterPersistable([
                    'name' => $data['name'] ?? $data['code'],
                    'country' => $data['country'] ?? '',
                    'city' => $data['city'] ?? null,
                    'unlocode' => $data['unlocode'] ?? null,
                    'code' => $data['code'],
                    'port_type' => $data['port_type'] ?? 'sea',
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'is_active' => (bool) ($data['is_active'] ?? true),
                ])
            );
        }

        fclose($handle);
        flash(translate('Ports imported successfully.'))->success();

        return back();
    }

    protected function validatedData(Request $request, ?B2BPort $port = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:30|unique:b2b_ports,code,' . ($port?->id ?? 'NULL'),
            'country' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'unlocode' => 'nullable|string|max:20',
            'timezone' => 'nullable|string|max:50',
            'port_type' => 'required|in:sea,air,rail,inland,dry port,dry_port',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_active' => 'nullable|boolean',
        ]) + [
            'is_active' => $request->boolean('is_active', true),
            'port_type' => str_replace(' ', '_', $request->input('port_type')),
        ];
    }
}
