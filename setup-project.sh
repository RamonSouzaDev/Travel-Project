
#!/bin/bash
set -e

# Cores para output no terminal
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funções de log
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificando pré-requisitos
check_requirements() {
    log_info "Verificando pré-requisitos..."
    
    # Verificar se o Docker está instalado
    if ! command -v docker &> /dev/null; then
        log_error "Docker não encontrado. Por favor instale o Docker antes de continuar."
        exit 1
    fi
    
    # Verificar se o Docker Compose está instalado
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose não encontrado. Por favor instale o Docker Compose antes de continuar."
        exit 1
    fi
    
    log_success "Todos os pré-requisitos estão presentes."
}


# Instalar dependências adicionais
install_dependencies() {
    log_info "Instalando dependências adicionais..."
    
    docker-compose exec app composer install
    docker-compose exec app composer require php-open-source-saver/jwt-auth

    
    log_success "Dependências instaladas com sucesso."
}

# Configuração dos arquivos Docker
setup_docker_config() {
    log_info "Configurando arquivos Docker..."
    
    # Criar diretório para arquivos Docker
    mkdir -p docker/nginx
    mkdir -p docker/mysql
    
    # Criar arquivo docker-compose.yml
    cat > docker-compose.yml << 'EOL'
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: viagens-app
    restart: unless-stopped
    working_dir: /var/www/
    volumes:
      - ./:/var/www
    networks:
      - viagens-network
    depends_on:
      - db
      - redis
    dns:
      - 8.8.8.8
      - 8.8.4.4

  nginx:
    image: nginx:alpine
    container_name: viagens-nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx:/etc/nginx/conf.d/
    networks:
      - viagens-network
    depends_on:
      - app

  db:
    image: mysql:8.0
    container_name: viagens-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_USER: ${DB_USERNAME}
      SERVICE_TAGS: dev
      SERVICE_NAME: mysql
    volumes:
      - ./docker/mysql:/docker-entrypoint-initdb.d
      - viagens-db-data:/var/lib/mysql
    networks:
      - viagens-network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-p${DB_PASSWORD}"]
      retries: 3
      timeout: 5s

  redis:
    image: redis:alpine
    container_name: viagens-redis
    restart: unless-stopped
    networks:
      - viagens-network

networks:
  viagens-network:
    driver: bridge

volumes:
  viagens-db-data:
EOL


    # Criar arquivo Dockerfile
    cat > Dockerfile << 'EOL'
FROM php:8.2-fpm

# Argumentos definidos no docker-compose.yml
ARG user=www-data
ARG uid=1000

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev

# Limpar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Obter Composer mais recente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar diretório do sistema
RUN mkdir -p /var/www

# Copiar o código da aplicação existente
COPY . /var/www

# Definir o diretório de trabalho
WORKDIR /var/www


RUN if [ ! -f ".env" ]; then touch .env; fi

# Definir permissões de pasta
RUN chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# Expor porta 9000 e iniciar servidor php-fpm
EXPOSE 9000
CMD ["php-fpm"]
EOL

    # Criar arquivo de configuração Nginx
    cat > docker/nginx/app.conf << 'EOL'
server {
    listen 80;
    index index.php index.html;
    error_log  /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
    root /var/www/public;
    
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
        gzip_static on;
    }
}
EOL

    # Atualizar arquivo .env.example para trabalhar com o Docker
    if [ -f ".env.example" ]; then cp .env.example .env; else touch .env; fi

    sed -i "s/DB_HOST=127.0.0.1/DB_HOST=db/g" .env
    sed -i "s/DB_DATABASE=laravel/DB_DATABASE=viagens_corporativas/g" .env
    sed -i "s/DB_USERNAME=root/DB_USERNAME=viagens_user/g" .env
    sed -i "s/DB_PASSWORD=/DB_PASSWORD=viagens_password/g" .env
    sed -i "s/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/g" .env
    
    # Adicionar configuração JWT no .env
    echo "JWT_SECRET=" >> .env
    echo "JWT_TTL=60" >> .env
    
    log_info "Subindo os containers com Docker Compose..."
    docker-compose up -d --build


    log_success "Arquivos Docker configurados com sucesso."
}

# Configuração das migrations, models, controllers e rotas
setup_app_structure() {
    log_info "Configurando estrutura da aplicação..."

    # Rodar o comando artisan dentro do container app
    docker-compose exec app php artisan make:migration create_travel_requests_table
    
    # Atualizar migration para users
    cat > database/migrations/2014_10_12_000000_create_users_table.php << 'EOL'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
EOL

    # Criar uma nova migração para travel_requests em vez de tentar encontrar uma existente
    log_info "Criando migração para travel_requests..."
    
    # Gerar um timestamp para o nome do arquivo
    TIMESTAMP=$(date +%Y_%m_%d_%H%M%S)
    TR_MIGRATION="database/migrations/${TIMESTAMP}_create_travel_requests_table.php"
    
    log_info "Criando arquivo de migração: $TR_MIGRATION"
    
    # Criar o arquivo com o conteúdo da migração
    cat > "$TR_MIGRATION" << 'EOL'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('travel_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('destination');
            $table->date('departure_date');
            $table->date('return_date');
            $table->enum('status', ['solicitado', 'aprovado', 'cancelado'])->default('solicitado');
            $table->text('reason_for_cancellation')->nullable();
            $table->timestamps();
            
            // Índices para otimizar buscas
            $table->index(['user_id', 'status']);
            $table->index(['departure_date', 'return_date']);
            $table->index('destination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_requests');
    }
};
EOL

    # Criar Model TravelRequest
    docker-compose exec app php artisan make:model TravelRequest
    
    # Atualizar Model User
    cat > app/Models/User.php << 'EOL'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * User travel requests relationship
     */
    public function travelRequests()
    {
        return $this->hasMany(TravelRequest::class);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
EOL

    # Atualizar Model TravelRequest
    cat > app/Models/TravelRequest.php << 'EOL'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\TravelRequestStatusUpdated;

class TravelRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'destination',
        'departure_date',
        'return_date',
        'status',
        'reason_for_cancellation',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
    ];

    /**
     * User relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include travel requests with specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('departure_date', [$startDate, $endDate])
                  ->orWhereBetween('return_date', [$startDate, $endDate])
                  ->orWhere(function ($query) use ($startDate, $endDate) {
                      $query->where('departure_date', '<=', $startDate)
                            ->where('return_date', '>=', $endDate);
                  });
        });
    }

    /**
     * Scope a query to filter by destination
     */
    public function scopeDestination($query, $destination)
    {
        return $query->where('destination', 'like', "%{$destination}%");
    }

    /**
     * Check if travel request can be cancelled
     */
    public function canBeCancelled(): bool
    {
        if ($this->status === 'cancelado') {
            return false;
        }
        
        if ($this->status === 'aprovado') {
            // Verifique se a data de partida é pelo menos 3 dias no futuro
            return $this->departure_date->diffInDays(now()) >= 3;
        }
        
        return true;
    }

    /**
     * Update status and notify user
     */
    public function updateStatus(string $status, ?string $reasonForCancellation = null): void
    {
        $oldStatus = $this->status;
        
        $this->status = $status;
        
        if ($status === 'cancelado' && $reasonForCancellation) {
            $this->reason_for_cancellation = $reasonForCancellation;
        }
        
        $this->save();
        
        // Notificar o usuário apenas se o status foi alterado
        if ($oldStatus !== $status) {
            $this->user->notify(new TravelRequestStatusUpdated($this));
        }
    }
}
EOL

    # Criar diretório para Notifications
    mkdir -p app/Notifications
    
    # Criar Notification para atualização de status
    cat > app/Notifications/TravelRequestStatusUpdated.php << 'EOL'
<?php

namespace App\Notifications;

use App\Models\TravelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var TravelRequest
     */
    protected $travelRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(TravelRequest $travelRequest)
    {
        $this->travelRequest = $travelRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusText = $this->getStatusText();
        
        $mailMessage = (new MailMessage)
                    ->subject("Solicitação de Viagem - {$statusText}")
                    ->greeting("Olá {$notifiable->name},")
                    ->line("Sua solicitação de viagem para {$this->travelRequest->destination} de {$this->travelRequest->departure_date->format('d/m/Y')} a {$this->travelRequest->return_date->format('d/m/Y')} foi {$statusText}.")
                    ->line("Status atual: {$this->travelRequest->status}");
        
        if ($this->travelRequest->status === 'cancelado' && $this->travelRequest->reason_for_cancellation) {
            $mailMessage->line("Motivo do cancelamento: {$this->travelRequest->reason_for_cancellation}");
        }
                    
        return $mailMessage->action('Ver Detalhes', url('/'))
                    ->line('Obrigado por usar nosso sistema de viagens corporativas!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'travel_request_id' => $this->travelRequest->id,
            'status' => $this->travelRequest->status,
            'destination' => $this->travelRequest->destination,
            'departure_date' => $this->travelRequest->departure_date->format('Y-m-d'),
            'return_date' => $this->travelRequest->return_date->format('Y-m-d'),
            'reason_for_cancellation' => $this->travelRequest->reason_for_cancellation,
        ];
    }
    
    /**
     * Get descriptive status text
     */
    protected function getStatusText(): string
    {
        return match($this->travelRequest->status) {
            'aprovado' => 'aprovada',
            'cancelado' => 'cancelada',
            default => $this->travelRequest->status
        };
    }
}
EOL

    # Criar Controllers
    docker-compose exec app php artisan make:controller AuthController
    docker-compose exec app php artisan make:controller TravelRequestController
    
    # Criar Form Requests
    docker-compose exec app php artisan make:request StoreTravelRequestRequest
    docker-compose exec app php artisan make:request UpdateTravelRequestStatusRequest
    
    # Criar Resource para TravelRequest
    docker-compose exec app php artisan make:resource TravelRequestResource
    
    # Atualizar AuthController
    cat > app/Http/Controllers/AuthController.php << 'EOL'
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Cria um novo usuário e retorna um token JWT
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = Auth::login($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário criado com sucesso',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    /**
     * Autentica o usuário e retorna um token JWT
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        $token = Auth::attempt($credentials);
        
        if (!$token) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }

    /**
     * Desloga o usuário (invalida o token)
     */
    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Logout realizado com sucesso',
        ]);
    }

    /**
     * Atualiza o token JWT
     */
    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
            'authorization' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }
}
EOL

    # Atualizar TravelRequestController
    cat > app/Http/Controllers/TravelRequestController.php << 'EOL'
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTravelRequestRequest;
use App\Http\Requests\UpdateTravelRequestStatusRequest;
use App\Http\Resources\TravelRequestResource;
use App\Models\TravelRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;

class TravelRequestController extends Controller
{
    /**
     * Exibe uma lista com todos os pedidos de viagem do usuário atual ou todos para admins.
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|string|in:solicitado,aprovado,cancelado',
            'destination' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = Auth::user();
        $query = $user->isAdmin() ? TravelRequest::query() : $user->travelRequests();

        // Filtrar por status
        if ($request->has('status')) {
            $query->withStatus($request->status);
        }

        // Filtrar por destino
        if ($request->has('destination')) {
            $query->destination($request->destination);
        }

        // Filtrar por período
        if ($request->has(['start_date', 'end_date'])) {
            $query->betweenDates($request->start_date, $request->end_date);
        }

        $travelRequests = $query->latest()->paginate(15);

        return TravelRequestResource::collection($travelRequests);
    }

    /**
     * Armazena um novo pedido de viagem.
     */
    public function store(StoreTravelRequestRequest $request)
    {
        $travelRequest = new TravelRequest($request->validated());
        $travelRequest->user_id = Auth::id();
        $travelRequest->save();

        return new TravelRequestResource($travelRequest);
    }

    /**
     * Exibe um pedido de viagem específico.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $travelRequest = TravelRequest::findOrFail($id);
        
        // Verifica se o usuário tem permissão para ver este pedido
        if (!$user->isAdmin() && $travelRequest->user_id !== $user->id) {
            return response()->json(['message' => 'Não autorizado a ver este pedido de viagem'], Response::HTTP_FORBIDDEN);
        }

        return new TravelRequestResource($travelRequest);
    }

    /**
     * Atualiza o status de um pedido de viagem.
     */
    public function updateStatus(UpdateTravelRequestStatusRequest $request, string $id)
    {
        $user = Auth::user();
        $travelRequest = TravelRequest::findOrFail($id);
        
        // Apenas administradores podem atualizar o status
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Apenas administradores podem atualizar o status'], Response::HTTP_FORBIDDEN);
        }
        
        // Verificar se o status pode ser alterado para 'cancelado'
        if ($request->status === 'cancelado' && !$travelRequest->canBeCancelled()) {
            return response()->json([
                'message' => 'Este pedido não pode ser cancelado. Verifique as regras de cancelamento.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $travelRequest->updateStatus($request->status, $request->reason_for_cancellation);
        
        return new TravelRequestResource($travelRequest);
    }

    /**
     * Cancela um pedido de viagem pelo solicitante.
     */
    public function cancel(Request $request, string $id)
    {
        $request->validate([
            'reason_for_cancellation' => 'nullable|string|max:500',
        ]);
        
        $user = Auth::user();
        $travelRequest = TravelRequest::findOrFail($id);
        
        // Verificar se o usuário é o proprietário do pedido
        if ($travelRequest->user_id !== $user->id) {
            return response()->json(['message' => 'Você só pode cancelar seus próprios pedidos'], Response::HTTP_FORBIDDEN);
        }
        
        // Verificar se o pedido pode ser cancelado
        if (!$travelRequest->canBeCancelled()) {
            return response()->json([
                'message' => 'Este pedido não pode ser cancelado. Verifique as regras de cancelamento.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $travelRequest->updateStatus('cancelado', $request->reason_for_cancellation);
        
        return new TravelRequestResource($travelRequest);
    }
}
EOL

    # Atualizar StoreTravelRequestRequest
    cat > app/Http/Requests/StoreTravelRequestRequest.php << 'EOL'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTravelRequestRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'destination' => 'required|string|max:255',
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date' => 'required|date|after_or_equal:departure_date',
        ];
    }
    
    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'destination.required' => 'O destino é obrigatório.',
            'departure_date.required' => 'A data de ida é obrigatória.',
            'departure_date.after_or_equal' => 'A data de ida deve ser hoje ou uma data futura.',
            'return_date.required' => 'A data de volta é obrigatória.',
            'return_date.after_or_equal' => 'A data de volta deve ser igual ou posterior à data de ida.',
        ];
    }
}
EOL

    # Atualizar UpdateTravelRequestStatusRequest
    cat > app/Http/Requests/UpdateTravelRequestStatusRequest.php << 'EOL'
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTravelRequestStatusRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'status' => 'required|string|in:aprovado,cancelado',
            'reason_for_cancellation' => 'required_if:status,cancelado|nullable|string|max:500',
        ];
    }
    
    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'O status deve ser aprovado ou cancelado.',
            'reason_for_cancellation.required_if' => 'O motivo do cancelamento é obrigatório quando o status é cancelado.',
        ];
    }
}
EOL

    # Atualizar TravelRequestResource
    cat > app/Http/Resources/TravelRequestResource.php << 'EOL'
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'destination' => $this->destination,
            'departure_date' => $this->departure_date->format('Y-m-d'),
            'return_date' => $this->return_date->format('Y-m-d'),
            'status' => $this->status,
            'reason_for_cancellation' => $this->reason_for_cancellation,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'can_be_cancelled' => $this->canBeCancelled(),
        ];
    }
}
EOL

    # Configurar rotas de API
    cat > routes/api.php << 'EOL'
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TravelRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rotas públicas de autenticação
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Rotas protegidas
Route::middleware('auth:api')->group(function () {
    // Rotas de autenticação
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    
    // Rotas de pedidos de viagem
    Route::get('travel-requests', [TravelRequestController::class, 'index']);
    Route::post('travel-requests', [TravelRequestController::class, 'store']);
    Route::get('travel-requests/{id}', [TravelRequestController::class, 'show']);
    Route::patch('travel-requests/{id}/status', [TravelRequestController::class, 'updateStatus']);
    Route::post('travel-requests/{id}/cancel', [TravelRequestController::class, 'cancel']);
});
EOL

    # Configurar JWT no AuthServiceProvider
    cat >> app/Providers/AuthServiceProvider.php << 'EOL'

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Configurar JWT Auth
        Auth::extend('jwt', function ($app, $name, array $config) {
            return new \PHPOpenSourceSaver\JWTAuth\JWTGuard(
                new \PHPOpenSourceSaver\JWTAuth\JWT(
                    $app['tymon.jwt.manager'],
                    $app['tymon.jwt.provider.auth'],
                    $app['tymon.jwt.provider.storage'],
                    $app['tymon.jwt.blacklist'],
                    $app['tymon.jwt.payload.factory']
                ),
                $app['auth']->createUserProvider($config['provider']),
                $app['request']
            );
        });
    }
EOL

    # Configurar middleware para JWT no Kernel
    cat >> app/Http/Kernel.php << 'EOL'
        'auth:api' => \PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate::class,
EOL

    log_success "Estrutura da aplicação configurada com sucesso."
}

# Criar testes automatizados
setup_tests() {
    log_info "Configurando testes automatizados..."
    
    # Atualizar phpunit.xml
    cat > phpunit.xml << 'EOL'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
EOL

    # Criar teste para Controller de Autenticação
    mkdir -p tests/Feature
    
    cat > tests/Feature/AuthTest.php << 'EOL'
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Teste para registro de usuário.
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                'authorization' => ['token', 'type'],
            ]);
            
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    /**
     * Teste para login de usuário.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                'authorization' => ['token', 'type'],
            ]);
    }

    /**
     * Teste para tentativa de login com credenciais inválidas.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Teste para logout de usuário.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Logout realizado com sucesso',
            ]);
    }
}
EOL

    # Criar teste para Controller de Pedidos de Viagem
    cat > tests/Feature/TravelRequestTest.php << 'EOL'
<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Usuário autenticado para testes
     */
    protected $user;
    
    /**
     * Token de autenticação
     */
    protected $token;
    
    /**
     * Setup do teste
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar um usuário e autenticar
        $this->user = User::factory()->create();
        $this->token = auth()->login($this->user);
    }
    
    /**
     * Teste para criar um pedido de viagem.
     */
    public function test_user_can_create_travel_request(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/travel-requests', [
                'destination' => 'São Paulo',
                'departure_date' => now()->addDays(10)->format('Y-m-d'),
                'return_date' => now()->addDays(15)->format('Y-m-d'),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 
                    'user', 
                    'destination', 
                    'departure_date', 
                    'return_date', 
                    'status', 
                    'created_at', 
                    'updated_at'
                ],
            ]);
            
        $this->assertDatabaseHas('travel_requests', [
            'user_id' => $this->user->id,
            'destination' => 'São Paulo',
            'status' => 'solicitado',
        ]);
    }

    /**
     * Teste para listar pedidos de viagem.
     */
    public function test_user_can_list_own_travel_requests(): void
    {
        // Criar alguns pedidos de viagem para o usuário
        TravelRequest::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/travel-requests');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 
                        'user', 
                        'destination', 
                        'departure_date', 
                        'return_date', 
                        'status', 
                        'created_at', 
                        'updated_at'
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Teste para visualizar um pedido de viagem específico.
     */
    public function test_user_can_view_own_travel_request(): void
    {
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $this->user->id,
        ]);
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/travel-requests/' . $travelRequest->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 
                    'user', 
                    'destination', 
                    'departure_date', 
                    'return_date', 
                    'status', 
                    'created_at', 
                    'updated_at'
                ],
            ]);
    }

    /**
     * Teste para verificar que um usuário não pode ver pedidos de outros usuários.
     */
    public function test_user_cannot_view_others_travel_request(): void
    {
        // Criar outro usuário e pedido de viagem
        $otherUser = User::factory()->create();
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $otherUser->id,
        ]);
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/travel-requests/' . $travelRequest->id);

        $response->assertStatus(403);
    }

    /**
     * Teste para verificar que um administrador pode atualizar o status de um pedido.
     */
    public function test_admin_can_update_travel_request_status(): void
    {
        // Criar usuário admin
        $adminUser = User::factory()->create([
            'role' => 'admin',
        ]);
        $adminToken = auth()->login($adminUser);
        
        // Criar pedido de viagem para um usuário normal
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'solicitado',
        ]);
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->patchJson('/api/travel-requests/' . $travelRequest->id . '/status', [
                'status' => 'aprovado',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'aprovado');
            
        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => 'aprovado',
        ]);
    }
    
    /**
     * Teste para verificar que um usuário normal não pode atualizar o status.
     */
    public function test_regular_user_cannot_update_travel_request_status(): void
    {
        // Criar pedido de viagem
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'solicitado',
        ]);
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson('/api/travel-requests/' . $travelRequest->id . '/status', [
                'status' => 'aprovado',
            ]);

        $response->assertStatus(403);
            
        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => 'solicitado', // O status não deve mudar
        ]);
    }
    
    /**
     * Teste para verificar que um usuário pode cancelar seu próprio pedido.
     */
    public function test_user_can_cancel_own_travel_request(): void
    {
        // Criar pedido de viagem
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'solicitado',
        ]);
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/travel-requests/' . $travelRequest->id . '/cancel', [
                'reason_for_cancellation' => 'Mudança de planos',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelado');
            
        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'status' => 'cancelado',
            'reason_for_cancellation' => 'Mudança de planos',
        ]);
    }
}
EOL

    # Criar factory para TravelRequest
    mkdir -p database/factories
    
    cat > database/factories/TravelRequestFactory.php << 'EOL'
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TravelRequest>
 */
class TravelRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'destination' => $this->faker->city(),
            'departure_date' => $this->faker->dateTimeBetween('+1 week', '+2 weeks'),
            'return_date' => $this->faker->dateTimeBetween('+3 weeks', '+4 weeks'),
            'status' => $this->faker->randomElement(['solicitado', 'aprovado', 'cancelado']),
            'reason_for_cancellation' => function (array $attributes) {
                return $attributes['status'] === 'cancelado' 
                    ? $this->faker->sentence() 
                    : null;
            },
        ];
    }
    
    /**
     * Define o estado do pedido como solicitado.
     */
    public function solicitado()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'solicitado',
            'reason_for_cancellation' => null,
        ]);
    }
    
    /**
     * Define o estado do pedido como aprovado.
     */
    public function aprovado()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aprovado',
            'reason_for_cancellation' => null,
        ]);
    }
    
    /**
     * Define o estado do pedido como cancelado.
     */
    public function cancelado()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelado',
            'reason_for_cancellation' => $this->faker->sentence(),
        ]);
    }
}
EOL

    log_success "Testes automatizados configurados com sucesso."
}

# Configurar pipeline GitHub Actions
setup_github_actions() {
    log_info "Configurando GitHub Actions..."
    
    mkdir -p .github/workflows
    
    cat > .github/workflows/laravel.yml << 'EOL'
name: Laravel CI/CD

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  laravel-tests:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: test_db
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
    - uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
    
    - uses: actions/checkout@v3
    
    - name: Copy .env
      run: cp .env.example .env
    
    - name: Install Dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress
    
    - name: Generate key
      run: php artisan key:generate
    
    - name: Directory Permissions
      run: chmod -R 777 storage bootstrap/cache
    
    - name: Create Database
      run: |
        mkdir -p database
        touch database/database.sqlite
    
    - name: Execute tests (Unit and Feature tests) via PHPUnit
      env:
        DB_CONNECTION: sqlite
        DB_DATABASE: database/database.sqlite
      run: vendor/bin/phpunit
    
    - name: Run Laravel Pint
      run: ./vendor/bin/pint --test

  deploy:
    needs: laravel-tests
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup SSH
      uses: webfactory/ssh-agent@v0.7.0
      with:
        ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY }}
    
    - name: Setup known_hosts
      run: |
        mkdir -p ~/.ssh
        ssh-keyscan -H ${{ secrets.SSH_HOST }} >> ~/.ssh/known_hosts
    
    - name: Deploy to Production
      run: |
        ssh ${{ secrets.SSH_USER }}@${{ secrets.SSH_HOST }} << 'EOF'
          cd ${{ secrets.PROJECT_PATH }}
          git pull origin main
          composer install --no-dev --optimize-autoloader
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
        EOF
EOL

    log_success "GitHub Actions configurado com sucesso."
}

# Atualizar README
create_readme() {
    log_info "Criando README.md..."
    
    cat > README.md << 'EOL'
# Microsserviço de Gerenciamento de Viagens Corporativas

Este é um microsserviço desenvolvido em Laravel para gerenciar pedidos de viagem corporativa, fornecendo uma API REST para operações de criação, atualização, consulta e listagem de pedidos.

## Tecnologias Utilizadas

- Laravel 12
- MySQL 8.0
- Redis
- Docker
- JWT para autenticação
- PHPUnit para testes automatizados

## Funcionalidades

- Criação de pedidos de viagem (destino, data de ida, data de volta)
- Atualização de status de pedidos (aprovado, cancelado)
- Consulta detalhada de pedidos
- Listagem de todos os pedidos com filtros (status, período, destino)
- Cancelamento de pedidos
- Notificações por e-mail para aprovações e cancelamentos
- Autenticação JWT
- Controle de acesso baseado em perfis (usuário e admin)

## Requisitos

- Docker
- Docker Compose
- Git

## Instalação e Configuração

### Passo 1: Clonar o repositório

```bash
git clone https://github.com/seu-usuario/viagens-corporativas.git
cd viagens-corporativas
```

### Passo 2: Configurar o ambiente

```bash
cp .env.example .env
```

Edite o arquivo `.env` com suas configurações desejadas (banco de dados, etc.)

### Passo 3: Iniciar os containers Docker

```bash
docker-compose up -d
```

### Passo 4: Instalar dependências e configurar o projeto

```bash
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed (opcional para dados de teste)
```

## Estrutura do Banco de Dados

- `users`: Armazena informações dos usuários
- `travel_requests`: Armazena os pedidos de viagem
- `notifications`: Armazena as notificações enviadas aos usuários

## Endpoints da API

### Autenticação
- `POST /api/register`: Registrar um novo usuário
- `POST /api/login`: Autenticar usuário e obter token JWT
- `POST /api/logout`: Deslogar (invalidar token)
- `POST /api/refresh`: Atualizar token JWT

### Pedidos de Viagem
- `GET /api/travel-requests`: Listar todos os pedidos de viagem do usuário atual (ou todos para admin)
- `POST /api/travel-requests`: Criar um novo pedido de viagem
- `GET /api/travel-requests/{id}`: Consultar um pedido de viagem específico
- `PATCH /api/travel-requests/{id}/status`: Atualizar o status de um pedido (apenas admin)
- `POST /api/travel-requests/{id}/cancel`: Cancelar um pedido de viagem (pelo solicitante)

## Filtros Disponíveis

Ao listar pedidos (`GET /api/travel-requests`), os seguintes filtros podem ser aplicados:

- `status`: Filtrar por status (solicitado, aprovado, cancelado)
- `destination`: Filtrar por destino
- `start_date` e `end_date`: Filtrar por período

Exemplo: `/api/travel-requests?status=aprovado&destination=São%20Paulo&start_date=2025-04-01&end_date=2025-04-30`

## Executando Testes

Para rodar os testes automatizados:

```bash
docker-compose exec app php artisan test
```

## Pipeline CI/CD

O projeto inclui um pipeline de CI/CD configurado com GitHub Actions que:
1. Executa testes automatizados
2. Verifica o estilo de código
3. Realiza deploy automático (quando configurado)

## Recursos Adicionais

- A API implementa validação de dados no backend
- Tratamento de erros apropriado
- Documentação detalhada
- Testes automatizados para todas as funcionalidades principais

## Contribuição

Para contribuir com o projeto:
1. Faça um fork do repositório
2. Crie uma branch para sua feature (`git checkout -b feature/nome-da-feature`)
3. Faça commit das suas alterações (`git commit -am 'Adiciona nova feature'`)
4. Envie para o GitHub (`git push origin feature/nome-da-feature`)
5. Crie um Pull Request
EOL

    log_success "README.md criado com sucesso."
}

main() {
    echo "========================================="
    echo "   Configuração do Projeto de Viagens    "
    echo "========================================="

    check_requirements
    setup_docker_config           # Sobe os containers primeiro
    install_dependencies          # Roda composer install no container
    setup_app_structure           # Só agora roda os php artisan
    setup_tests
    setup_github_actions
    create_readme

    echo "========================================="
    echo "      Projeto configurado com sucesso    "
    echo "========================================="
    echo ""
    echo "Para iniciar o projeto, execute:"
    echo "cd viagens-corporativas"
    echo "docker-compose up -d"
    echo ""
    echo "Após iniciar, configure o JWT com:"
    echo "docker-compose exec app php artisan jwt:secret"
    echo ""
    echo "E execute as migrations:"
    echo "docker-compose exec app php artisan migrate"
    echo ""
    echo "A API estará disponível em: http://localhost:8000/api"
}


# Executa o script principal
main