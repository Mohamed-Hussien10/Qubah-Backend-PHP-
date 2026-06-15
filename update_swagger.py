import os
import re

controllers = {
    'EducationalStageController': ('Educational Stages', 'educational-stages'),
    'GradeController': ('Grades', 'grades'),
    'SectionController': ('Sections', 'sections'),
    'SubjectController': ('Subjects', 'subjects'),
    'UnitController': ('Units', 'units'),
    'LessonController': ('Lessons', 'lessons'),
    'LessonFileController': ('Lesson Files', 'lesson-files'),
}

base_path = r'd:\Flutter\Qubah App\qubah_learning_app\backend\app\Http\Controllers\Api\v1'

for controller_name, (tag, endpoint) in controllers.items():
    file_path = os.path.join(base_path, f'{controller_name}.php')
    if not os.path.exists(file_path):
        print(f"File not found: {file_path}")
        continue
        
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Add OpenApi use statement if not exists
    if 'use OpenApi\\Attributes as OA;' not in content:
        content = content.replace('use Illuminate\\Http\\Request;', 'use Illuminate\\Http\\Request;\nuse OpenApi\\Attributes as OA;')

    # Annotate index (if exists)
    if 'public function index()' in content and '#[OA\\Get' not in content:
        index_anno = f"""
    #[OA\\Get(
        path: '/api/v1/{endpoint}',
        summary: 'Get all {tag.lower()}',
        tags: ['{tag}'],
        security: [['sanctum' => []]],
        responses: [
            new OA\\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function index()"""
        content = content.replace('    public function index()', index_anno)

    # Annotate show (if exists)
    if 'public function show($id)' in content and "path: '/api/v1/{endpoint}/{id}'" not in content:
        show_anno = f"""
    #[OA\\Get(
        path: '/api/v1/{endpoint}/{{id}}',
        summary: 'Get a specific {tag.lower().rstrip("s")}',
        tags: ['{tag}'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\\Parameter(name: 'id', in: 'path', required: true, schema: new OA\\Schema(type: 'integer'))
        ],
        responses: [
            new OA\\Response(response: 200, description: 'Successful operation')
        ]
    )]
    public function show($id)"""
        content = content.replace('    public function show($id)', show_anno)

    # Write back
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)

print("Swagger annotations added successfully.")
