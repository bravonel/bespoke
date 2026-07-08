<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectWorkload;
use App\Support\OperationalLabels;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_application_defaults_to_spanish(): void
    {
        $this->assertSame('es', config('app.locale'));
        $this->assertSame('es', config('app.fallback_locale'));
        $this->assertSame('America/Mexico_City', config('app.timezone'));
    }

    public function test_auth_password_and_mail_messages_are_spanish(): void
    {
        $this->assertSame('Estas credenciales no coinciden con nuestros registros.', __('auth.failed'));
        $this->assertSame('Te enviamos el enlace para restablecer tu contraseña.', __('passwords.sent'));
        $this->assertSame('Restablecer contraseña', __('Reset Password'));
        $this->assertSame('Verificar correo electrónico', __('Verify Email Address'));
    }

    public function test_validation_messages_use_spanish_attributes(): void
    {
        $validator = Validator::make(
            ['email' => ''],
            ['email' => ['required', 'email']]
        );

        $this->assertSame(
            'El campo correo electrónico es obligatorio.',
            $validator->errors()->first('email')
        );
    }

    public function test_operational_labels_are_spanish(): void
    {
        $this->assertSame('Activo', OperationalLabels::get('active'));
        $this->assertSame('En revisión', OperationalLabels::get('in_review'));
        $this->assertSame('Resumen inicial', OperationalLabels::get('brief'));
        $this->assertSame('Listo para enviar', OperationalLabels::get('ready_to_submit'));
        $this->assertSame('Redacción', OperationalLabels::get('Copy'));
        $this->assertSame('Médico', OperationalLabels::get('Medical'));
        $this->assertSame('Redes sociales', OperationalLabels::get('Social Media'));
        $this->assertSame('Campaña', Project::materialTypeLabel('campaign'));
        $this->assertSame('Redacción', ProjectWorkload::roleOptions()['copy']);
    }
}
