<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SettingsController extends Controller
{
    /**
     * Get app settings.
     */
    #[OA\Get(
        path: '/api/v1/settings',
        operationId: 'getSettings',
        summary: 'Get application settings',
        tags: ['Settings'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Successful operation', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'appName', type: 'string'),
                    new OA\Property(property: 'logoUrl', type: 'string'),
                    new OA\Property(property: 'contactEmail', type: 'string'),
                    new OA\Property(property: 'contactPhone', type: 'string'),
                    new OA\Property(property: 'maintenanceMode', type: 'boolean'),
                    new OA\Property(property: 'socialLinks', type: 'object', properties: [
                        new OA\Property(property: 'facebook', type: 'string'),
                        new OA\Property(property: 'twitter', type: 'string'),
                        new OA\Property(property: 'instagram', type: 'string'),
                        new OA\Property(property: 'youtube', type: 'string')
                    ]),
                    new OA\Property(property: 'baseUrl', type: 'string'),
                    new OA\Property(property: 'apiKey', type: 'string')
                ])
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function getSettings(): JsonResponse
    {
        return response()->json([
            'data' => [
                'appName' => Setting::getValue('app_name', 'قبة المعرفة'),
                'logoUrl' => Setting::getValue('logo_url', ''),
                'contactEmail' => Setting::getValue('contact_email', 'support@qubah.com'),
                'contactPhone' => Setting::getValue('contact_phone', '+966500000000'),
                'maintenanceMode' => (bool) Setting::getValue('maintenance_mode', false),
                'socialLinks' => Setting::getValue('social_links', [
                    'facebook' => '',
                    'twitter' => '',
                    'instagram' => '',
                    'youtube' => '',
                ]),
                'baseUrl' => url('/api/v1'),
                'apiKey' => 'qubah_secret_api_key_2026',
            ]
        ]);
    }

    /**
     * Update settings.
     */
    #[OA\Put(
        path: '/api/v1/settings',
        operationId: 'updateSettings',
        summary: 'Update application settings',
        tags: ['Settings'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'appName', type: 'string', example: 'قبة المعرفة الجديدة'),
                    new OA\Property(property: 'logoUrl', type: 'string', example: 'https://example.com/logo.png'),
                    new OA\Property(property: 'contactEmail', type: 'string', example: 'info@qubah.com'),
                    new OA\Property(property: 'contactPhone', type: 'string', example: '+966512345678'),
                    new OA\Property(property: 'maintenanceMode', type: 'boolean', example: false),
                    new OA\Property(property: 'socialLinks', type: 'object', properties: [
                        new OA\Property(property: 'facebook', type: 'string'),
                        new OA\Property(property: 'twitter', type: 'string'),
                        new OA\Property(property: 'instagram', type: 'string'),
                        new OA\Property(property: 'youtube', type: 'string')
                    ])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Settings updated successfully', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'object')
            ])),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function updateSettings(Request $request): JsonResponse
    {
        if ($request->has('appName')) {
            Setting::setValue('app_name', $request->input('appName'));
        }
        if ($request->has('logoUrl')) {
            Setting::setValue('logo_url', $request->input('logoUrl'));
        }
        if ($request->has('contactEmail')) {
            Setting::setValue('contact_email', $request->input('contactEmail'));
        }
        if ($request->has('contactPhone')) {
            Setting::setValue('contact_phone', $request->input('contactPhone'));
        }
        if ($request->has('maintenanceMode')) {
            Setting::setValue('maintenance_mode', $request->input('maintenanceMode'));
        }
        if ($request->has('socialLinks')) {
            Setting::setValue('social_links', $request->input('socialLinks'));
        }

        return $this->getSettings();
    }
}
