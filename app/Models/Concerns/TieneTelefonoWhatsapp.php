<?php

namespace App\Models\Concerns;

trait TieneTelefonoWhatsapp
{
    /**
     * Los 10 dígitos "de WhatsApp" (área + número, sin 0 ni 15 ni el 54/9 de
     * país) para precargar un link de wa.me — sea cual sea el formato en
     * que se haya cargado `telefono` (con o sin código de país, con
     * espacios/guiones), alcanza con quedarse con los últimos 10 dígitos.
     * Devuelve null si no hay teléfono cargado.
     */
    public function telefonoSoloDigitos(): ?string
    {
        if (blank($this->telefono)) {
            return null;
        }

        $digitos = preg_replace('/\D+/', '', $this->telefono);

        return $digitos !== '' ? substr($digitos, -10) : null;
    }
}
