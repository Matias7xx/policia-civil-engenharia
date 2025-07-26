<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Contracts\Auditable;

class AuditServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Registrar resolver
        $this->app->bind('OwenIt\Auditing\Contracts\AuditableResolver', function () {
            return new class {
                public function resolveAuditable($model)
                {
                    if (is_string($model)) {
                        $instance = app($model);
                        
                        // Verificar se implementa a interface Auditable
                        if (!$instance instanceof Auditable) {
                            throw new \InvalidArgumentException(
                                "Model {$model} must implement OwenIt\\Auditing\\Contracts\\Auditable"
                            );
                        }
                        
                        return $instance;
                    }
                    
                    // Verificar se o modelo implementa a interface
                    if (!$model instanceof Auditable) {
                        throw new \InvalidArgumentException(
                            "Model must implement OwenIt\\Auditing\\Contracts\\Auditable"
                        );
                    }
                    
                    return $model;
                }
            };
        });
    }

    public function boot()
    {
        // Configurar eventos globais de auditoria
        Audit::creating(function ($audit) {
            // Garantir que o usuário seja capturado
            if (!$audit->user_id && auth()->check()) {
                $audit->user_id = auth()->id();
            }

            // Adicionar informações do usuário
            if (auth()->check()) {
                $audit->user_type = get_class(auth()->user());
                
                $user = auth()->user();
                $tags = [];
                
                // Verificar se o usuário tem matrícula
                if (method_exists($user, 'matricula') && $user->matricula) {
                    $tags[] = "matricula:{$user->matricula}";
                }
                
                // Adicionar permissão
                if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin) {
                    $tags[] = "nivel:superadmin";
                } else {
                    $tags[] = "nivel:admin";
                }
                
                // Converter tags para string
                if (!empty($tags)) {
                    $existingTags = $audit->tags ? $audit->tags . ',' : '';
                    $audit->tags = $existingTags . implode(',', $tags);
                }
            }

            // Adicionar informações da requisição
            if (request()) {
                // IP
                if (!$audit->ip_address) {
                    $audit->ip_address = request()->ip();
                }
                
                // User Agent
                if (!$audit->user_agent) {
                    $audit->user_agent = request()->userAgent();
                }
                
                // URL
                if (!$audit->url) {
                    $audit->url = request()->fullUrl();
                }
            }
        });

        // Event listener para quando uma auditoria é criada
        Audit::created(function ($audit) {
            // Log para debug
            if (config('app.debug')) {
                \Log::info('Auditoria criada', [
                    'id' => $audit->id,
                    'event' => $audit->event,
                    'model' => $audit->auditable_type,
                    'model_id' => $audit->auditable_id,
                    'user_id' => $audit->user_id,
                    'tags' => $audit->tags
                ]);
            }
        });
    }
}