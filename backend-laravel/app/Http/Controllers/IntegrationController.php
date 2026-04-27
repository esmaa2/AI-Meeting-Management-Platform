<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    const PROVIDERS = ['zoom', 'slack', 'asana', 'microsoft_teams'];

    // GET /api/integrations
    public function index(Request $request): JsonResponse
    {
        $integrations = Integration::where('user_id', $request->user()->id)->get();

        // Return all providers with current status
        $result = collect(self::PROVIDERS)->map(function (string $provider) use ($integrations) {
            $integration = $integrations->firstWhere('provider', $provider);
            return [
                'provider'    => $provider,
                'status'      => $integration?->status ?? 'disconnected',
                'meta'        => $integration?->meta,
                'connected_at'=> $integration?->updated_at,
            ];
        });

        return response()->json(['integrations' => $result]);
    }

    // POST /api/integrations/{provider}/connect
    public function connect(Request $request, string $provider): JsonResponse
    {
        if (!in_array($provider, self::PROVIDERS)) {
            abort(422, 'Unknown provider.');
        }

        $data = $request->validate([
            'access_token'  => ['nullable', 'string'],
            'refresh_token' => ['nullable', 'string'],
            'meta'          => ['nullable', 'array'],
        ]);

        $integration = Integration::updateOrCreate(
            ['user_id' => $request->user()->id, 'provider' => $provider],
            [
                'status'        => 'connected',
                'access_token'  => $data['access_token']  ?? null,
                'refresh_token' => $data['refresh_token'] ?? null,
                'meta'          => $data['meta']           ?? null,
            ]
        );

        return response()->json([
            'message'     => ucfirst(str_replace('_', ' ', $provider)) . ' connected.',
            'integration' => [
                'provider' => $integration->provider,
                'status'   => $integration->status,
                'meta'     => $integration->meta,
            ],
        ]);
    }

    // DELETE /api/integrations/{provider}
    public function disconnect(Request $request, string $provider): JsonResponse
    {
        Integration::where('user_id', $request->user()->id)
                   ->where('provider', $provider)
                   ->update(['status' => 'disconnected', 'access_token' => null, 'refresh_token' => null]);

        return response()->json(['message' => ucfirst($provider) . ' disconnected.']);
    }

    // PATCH /api/integrations/{provider}
    public function update(Request $request, string $provider): JsonResponse
    {
        $data = $request->validate([
            'meta' => ['required', 'array'],
        ]);

        Integration::where('user_id', $request->user()->id)
                   ->where('provider', $provider)
                   ->update(['meta' => $data['meta']]);

        return response()->json(['message' => 'Integration settings updated.']);
    }
}