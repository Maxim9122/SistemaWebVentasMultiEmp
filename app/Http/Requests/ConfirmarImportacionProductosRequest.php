<?php

namespace App\Http\Requests;

use App\Services\MapeoColumnasService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Validator;

class ConfirmarImportacionProductosRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'mapeo' => ['required', 'array'],
            'mapeo.*' => ['nullable', 'string', 'in:'.implode(',', MapeoColumnasService::CAMPOS)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->datosImportacion()) {
                $validator->errors()->add('token', 'La importación expiró o no es válida. Volvé a subir el archivo.');

                return;
            }

            $mapeo = array_filter($this->input('mapeo', []));

            if (! in_array('nombre', $mapeo, true)) {
                $validator->errors()->add('mapeo', 'Tenés que asignar alguna columna al campo "Nombre".');
            }

            if (! in_array('precio', $mapeo, true)) {
                $validator->errors()->add('mapeo', 'Tenés que asignar alguna columna al campo "Precio".');
            }

            if (count(array_diff_assoc($mapeo, array_unique($mapeo))) > 0) {
                $validator->errors()->add('mapeo', 'No podés asignar dos columnas al mismo campo.');
            }
        });
    }

    /**
     * @return array{empresa_id: int, ruta: string}|null
     */
    public function datosImportacion(): ?array
    {
        $datos = Cache::get('import-productos:'.$this->input('token'));

        if (! $datos || $datos['empresa_id'] !== $this->user()->empresa_id) {
            return null;
        }

        return $datos;
    }
}
