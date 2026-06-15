<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Qubah Learning API',
    description: 'REST API for the Qubah Learning educational platform. Provides endpoints for authentication, subjects, topics, content, progress tracking, and parent dashboard.',
    contact: new OA\Contact(name: 'Qubah Team', email: 'support@qubah.com'),
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Local Development Server',
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Authenticate via POST /api/v1/auth/login then paste the access_token here.',
)]
#[OA\Tag(name: 'Authentication', description: 'Register, login, and logout')]
#[OA\Tag(name: 'Educational Stages', description: 'Top level educational stages')]
#[OA\Tag(name: 'Grades', description: 'Grades belonging to a stage')]
#[OA\Tag(name: 'Sections', description: 'Sections belonging to a grade')]
#[OA\Tag(name: 'Subjects', description: 'Subjects belonging to a section')]
#[OA\Tag(name: 'Units', description: 'Units belonging to a subject')]
#[OA\Tag(name: 'Lessons', description: 'Lessons belonging to a unit')]
#[OA\Tag(name: 'Lesson Files', description: 'Media files belonging to a lesson')]
#[OA\Tag(name: 'Progress', description: 'Track and summarize learning progress')]
#[OA\Tag(name: 'Parent Dashboard', description: 'Parent-only: view children and their progress')]
#[OA\Tag(name: 'User Management', description: 'Manage user accounts, roles, active status')]
#[OA\Tag(name: 'Plans & Subscriptions', description: 'Manage plans and student subscriptions')]
#[OA\Tag(name: 'Notifications', description: 'Create and dispatch notifications')]
#[OA\Tag(name: 'Settings', description: 'Configure application settings')]
#[OA\Tag(name: 'Dashboard', description: 'Dashboard metrics and activity logs')]
#[OA\Tag(name: 'Analytics', description: 'System-wide analytics and reporting')]
abstract class Controller
{
    //
}
