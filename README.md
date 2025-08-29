# 🐾 Yeti Express API

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-red?style=for-the-badge&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.2+-blue?style=for-the-badge&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
</p>

Una API robusta y modular construida con Laravel para gestionar servicios de entrega express. El sistema Yeti Express está diseñado con una arquitectura modular que permite el manejo integral de clientes, empleados, servicios de entrega, facturación y más.

## 🚀 Características Principales

- **Gestión de Autenticación**: Sistema completo de autenticación y autorización con Laravel Sanctum
- **Gestión de Clientes**: CRUD completo para administración de clientes
- **Gestión de Empleados**: Control de personal y roles
- **Servicios de Entrega**: Administración de entregas y seguimiento
- **Sistema de Facturación**: Generación de facturas y reportes financieros
- **Gestión de Deudas**: Control de pagos pendientes y seguimiento
- **Mensajería**: Sistema integrado de mensajeros y rutas
- **Generación de PDFs**: Reportes y documentos automatizados con DomPDF
- **Arquitectura Modular**: Organización por módulos independientes

## 📁 Estructura del Proyecto

```
app/
├── Auth/           # Autenticación y autorización
├── Cash/           # Gestión de caja y pagos
├── Client/         # Gestión de clientes
├── CompanyBill/    # Facturación empresarial
├── Core/           # Kernel y servicios centrales
├── Courier/        # Gestión de mensajeros
├── Debt/           # Gestión de deudas
├── Delivery/       # Servicios de entrega
├── Employee/       # Gestión de empleados
├── Service/        # Servicios generales
└── Shared/         # Servicios compartidos
```

## 🛠️ Tecnologías Utilizadas

- **Backend**: Laravel 12.x
- **PHP**: 8.2+
- **Base de Datos**: MySQL/PostgreSQL
- **Autenticación**: Laravel Sanctum
- **PDF Generation**: DomPDF
- **Frontend Assets**: Vite + TailwindCSS
- **Containerización**: Docker (Laravel Sail)
- **Testing**: PHPUnit

## 📋 Requisitos del Sistema

- PHP 8.2 o superior
- Composer
- Node.js 18+ y npm
- MySQL 8.0+ o PostgreSQL 13+
- Docker (opcional, para desarrollo con Sail)

## 🚀 Instalación

### Método 1: Instalación Tradicional

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/GabrielEVP/yeti-express-api.git
   cd yeti-express-api
   ```

2. **Instalar dependencias de PHP**
   ```bash
   composer install
   ```

3. **Instalar dependencias de Node.js**
   ```bash
   npm install
   ```

4. **Configurar variables de entorno**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configurar base de datos**
   ```bash
   # Editar .env con tus credenciales de BD
   php artisan migrate
   php artisan db:seed
   ```

6. **Ejecutar el servidor**
   ```bash
   php artisan serve
   npm run dev
   ```

### Método 2: Con Docker (Laravel Sail)

1. **Clonar y configurar**
   ```bash
   git clone https://github.com/GabrielEVP/yeti-express-api.git
   cd yeti-express-api
   composer install
   cp .env.example .env
   ```

2. **Levantar los contenedores**
   ```bash
   ./vendor/bin/sail up -d
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run dev
   ```

## 🧪 Testing

Ejecutar las pruebas unitarias y de integración:

```bash
# Instalación tradicional
php artisan test

# Con Docker Sail
./vendor/bin/sail artisan test
```

## 📚 API Endpoints

La API está organizada por módulos. Principales endpoints:

- **Auth**: `/api/auth/*` - Autenticación y registro
- **Clients**: `/api/clients/*` - Gestión de clientes
- **Employees**: `/api/employees/*` - Gestión de empleados
- **Deliveries**: `/api/deliveries/*` - Servicios de entrega
- **Bills**: `/api/bills/*` - Facturación
- **Debts**: `/api/debts/*` - Gestión de deudas
- **Couriers**: `/api/couriers/*` - Mensajeros

## 🔧 Configuración

### Variables de Entorno Principales

```env
APP_NAME="Yeti Express API"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yeti_express
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost:3000
```

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

### Estándares de Código

- Seguir PSR-12 para PHP
- Usar Laravel Pint para formateo: `./vendor/bin/pint`
- Mantener cobertura de tests > 80%
- Documentar nuevos endpoints en la documentación API

## 📝 Changelog

Ver [CHANGELOG.md](CHANGELOG.md) para ver los cambios en cada versión.

## 🐛 Reportar Bugs

Si encuentras un bug, por favor crea un issue en GitHub con:
- Descripción detallada del problema
- Pasos para reproducir
- Versión de PHP y Laravel
- Logs relevantes

## 👥 Equipo

- **Gabriel Vargas** - *Desarrollador Principal* - [@GabrielEVP](https://github.com/GabrielEVP)

## 📄 Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## 🙏 Agradecimientos

- Laravel Framework por la excelente base
- Comunidad de Laravel por las mejores prácticas
- Contribuidores y testers del proyecto

---

<p align="center">
  Desarrollado con ❤️ por Gabriel Vargas
</p>
