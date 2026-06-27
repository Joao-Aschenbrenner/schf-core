<?php

namespace App\Services;

class ApiDocumentationService
{
    public function generate(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'SCHF Core API',
                'description' => 'API do Sistema de Controle Hospitalar e Financeiro',
                'version' => config('app.version', '1.2.0'),
                'contact' => [
                    'name' => 'SCHF Team',
                    'url' => 'https://github.com/Joao-Aschenbrenner/schf-core',
                ],
            ],
            'servers' => [
                ['url' => config('app.url', 'http://localhost:9080'), 'description' => 'Local'],
            ],
            'paths' => $this->getPaths(),
            'components' => $this->getComponents(),
            'security' => [
                ['sanctum' => []],
            ],
        ];
    }

    protected function getPaths(): array
    {
        return [
            '/api/health' => [
                'get' => [
                    'summary' => 'Health check',
                    'tags' => ['System'],
                    'responses' => ['200' => ['description' => 'OK']],
                ],
            ],
            '/api/auth/login' => [
                'post' => [
                    'summary' => 'Login',
                    'tags' => ['Auth'],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['email', 'password'],
                                    'properties' => [
                                        'email' => ['type' => 'string', 'format' => 'email'],
                                        'password' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/api/organizations' => [
                'get' => [
                    'summary' => 'Listar organizações',
                    'tags' => ['Organizations'],
                    'security' => [['sanctum' => []]],
                ],
                'post' => [
                    'summary' => 'Criar organização',
                    'tags' => ['Organizations'],
                    'security' => [['sanctum' => []]],
                ],
            ],
            '/api/setup/status' => [
                'get' => [
                    'summary' => 'Status do setup',
                    'tags' => ['Setup'],
                ],
            ],
            '/api/setup/organization' => [
                'post' => [
                    'summary' => 'Criar organização via wizard',
                    'tags' => ['Setup'],
                ],
            ],
            '/api/setup/admin' => [
                'post' => [
                    'summary' => 'Criar admin via wizard',
                    'tags' => ['Setup'],
                ],
            ],
            '/api/setup/complete' => [
                'post' => [
                    'summary' => 'Finalizar setup',
                    'tags' => ['Setup'],
                ],
            ],
            '/api/admin/updates/check' => [
                'get' => [
                    'summary' => 'Verificar atualizações',
                    'tags' => ['Updates'],
                    'security' => [['sanctum' => []]],
                ],
            ],
            '/api/admin/updates/versions' => [
                'get' => [
                    'summary' => 'Listar versões disponíveis',
                    'tags' => ['Updates'],
                    'security' => [['sanctum' => []]],
                ],
            ],
            '/api/admin/updates/history' => [
                'get' => [
                    'summary' => 'Histórico de atualizações',
                    'tags' => ['Updates'],
                    'security' => [['sanctum' => []]],
                ],
            ],
        ];
    }

    protected function getComponents(): array
    {
        return [
            'securitySchemes' => [
                'sanctum' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'Laravel Sanctum Token',
                ],
            ],
            'schemas' => [
                'Organization' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'cnpj' => ['type' => 'string'],
                        'city' => ['type' => 'string'],
                        'state' => ['type' => 'string'],
                        'email' => ['type' => 'string'],
                        'phone' => ['type' => 'string'],
                        'is_active' => ['type' => 'boolean'],
                        'is_primary' => ['type' => 'boolean'],
                    ],
                ],
                'User' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'email' => ['type' => 'string'],
                        'organization_id' => ['type' => 'integer'],
                        'is_master' => ['type' => 'boolean'],
                    ],
                ],
                'UpdateCheck' => [
                    'type' => 'object',
                    'properties' => [
                        'current_version' => ['type' => 'string'],
                        'latest_version' => ['type' => 'string'],
                        'update_available' => ['type' => 'boolean'],
                        'is_compatible' => ['type' => 'boolean'],
                    ],
                ],
            ],
        ];
    }
}