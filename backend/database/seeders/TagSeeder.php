<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

/**
 * Class TagSeeder
 *
 * Seeds the tags table with initial data for content categorization.
 */
class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            // Programming Languages
            ['name' => 'PHP', 'slug' => 'php', 'color' => '#777BB4', 'is_featured' => true],
            ['name' => 'JavaScript', 'slug' => 'javascript', 'color' => '#F7DF1E', 'is_featured' => true],
            ['name' => 'TypeScript', 'slug' => 'typescript', 'color' => '#3178C6', 'is_featured' => true],
            ['name' => 'Python', 'slug' => 'python', 'color' => '#3776AB', 'is_featured' => true],
            ['name' => 'Go', 'slug' => 'go', 'color' => '#00ADD8', 'is_featured' => false],
            ['name' => 'Rust', 'slug' => 'rust', 'color' => '#DEA584', 'is_featured' => false],
            ['name' => 'Java', 'slug' => 'java', 'color' => '#007396', 'is_featured' => false],
            ['name' => 'C#', 'slug' => 'csharp', 'color' => '#239120', 'is_featured' => false],

            // Frameworks & Libraries
            ['name' => 'Laravel', 'slug' => 'laravel', 'color' => '#FF2D20', 'is_featured' => true],
            ['name' => 'Vue.js', 'slug' => 'vuejs', 'color' => '#4FC08D', 'is_featured' => true],
            ['name' => 'React', 'slug' => 'react', 'color' => '#61DAFB', 'is_featured' => true],
            ['name' => 'Next.js', 'slug' => 'nextjs', 'color' => '#000000', 'is_featured' => true],
            ['name' => 'Tailwind CSS', 'slug' => 'tailwindcss', 'color' => '#06B6D4', 'is_featured' => true],
            ['name' => 'Node.js', 'slug' => 'nodejs', 'color' => '#339933', 'is_featured' => true],
            ['name' => 'Express', 'slug' => 'express', 'color' => '#000000', 'is_featured' => false],
            ['name' => 'Django', 'slug' => 'django', 'color' => '#092E20', 'is_featured' => false],
            ['name' => 'Flask', 'slug' => 'flask', 'color' => '#000000', 'is_featured' => false],
            ['name' => 'Symfony', 'slug' => 'symfony', 'color' => '#000000', 'is_featured' => false],

            // Topics
            ['name' => 'Tutorial', 'slug' => 'tutorial', 'color' => '#3B82F6', 'is_featured' => false],
            ['name' => 'Beginner', 'slug' => 'beginner', 'color' => '#10B981', 'is_featured' => false],
            ['name' => 'Advanced', 'slug' => 'advanced', 'color' => '#EF4444', 'is_featured' => false],
            ['name' => 'Web Development', 'slug' => 'web-development', 'color' => '#8B5CF6', 'is_featured' => true],
            ['name' => 'Backend', 'slug' => 'backend', 'color' => '#6B7280', 'is_featured' => false],
            ['name' => 'Frontend', 'slug' => 'frontend', 'color' => '#EC4899', 'is_featured' => false],
            ['name' => 'Full Stack', 'slug' => 'full-stack', 'color' => '#F59E0B', 'is_featured' => false],
            ['name' => 'Database', 'slug' => 'database', 'color' => '#06B6D4', 'is_featured' => false],
            ['name' => 'API', 'slug' => 'api', 'color' => '#14B8A6', 'is_featured' => false],
            ['name' => 'REST API', 'slug' => 'rest-api', 'color' => '#0D9488', 'is_featured' => false],
            ['name' => 'GraphQL', 'slug' => 'graphql', 'color' => '#E535AB', 'is_featured' => false],

            // DevOps & Infrastructure
            ['name' => 'DevOps', 'slug' => 'devops', 'color' => '#3B82F6', 'is_featured' => false],
            ['name' => 'Docker', 'slug' => 'docker', 'color' => '#2496ED', 'is_featured' => true],
            ['name' => 'Kubernetes', 'slug' => 'kubernetes', 'color' => '#326CE5', 'is_featured' => false],
            ['name' => 'AWS', 'slug' => 'aws', 'color' => '#FF9900', 'is_featured' => true],
            ['name' => 'Azure', 'slug' => 'azure', 'color' => '#0078D4', 'is_featured' => false],
            ['name' => 'GCP', 'slug' => 'gcp', 'color' => '#4285F4', 'is_featured' => false],
            ['name' => 'CI/CD', 'slug' => 'cicd', 'color' => '#F7DF1E', 'is_featured' => false],
            ['name' => 'GitHub Actions', 'slug' => 'github-actions', 'color' => '#2088FF', 'is_featured' => false],

            // Best Practices
            ['name' => 'Security', 'slug' => 'security', 'color' => '#EF4444', 'is_featured' => true],
            ['name' => 'Performance', 'slug' => 'performance', 'color' => '#10B981', 'is_featured' => true],
            ['name' => 'Testing', 'slug' => 'testing', 'color' => '#8B5CF6', 'is_featured' => true],
            ['name' => 'Clean Code', 'slug' => 'clean-code', 'color' => '#06B6D4', 'is_featured' => false],
            ['name' => 'Design Patterns', 'slug' => 'design-patterns', 'color' => '#EC4899', 'is_featured' => false],
            ['name' => 'Architecture', 'slug' => 'architecture', 'color' => '#6366F1', 'is_featured' => false],

            // Tools
            ['name' => 'Git', 'slug' => 'git', 'color' => '#F05032', 'is_featured' => false],
            ['name' => 'VS Code', 'slug' => 'vscode', 'color' => '#007ACC', 'is_featured' => false],
            ['name' => 'Postman', 'slug' => 'postman', 'color' => '#FF6C37', 'is_featured' => false],
            ['name' => 'MySQL', 'slug' => 'mysql', 'color' => '#4479A1', 'is_featured' => false],
            ['name' => 'PostgreSQL', 'slug' => 'postgresql', 'color' => '#336791', 'is_featured' => false],
            ['name' => 'MongoDB', 'slug' => 'mongodb', 'color' => '#47A248', 'is_featured' => false],
            ['name' => 'Redis', 'slug' => 'redis', 'color' => '#DC382D', 'is_featured' => false],
            ['name' => 'Elasticsearch', 'slug' => 'elasticsearch', 'color' => '#005571', 'is_featured' => false],

            // Concepts
            ['name' => 'Authentication', 'slug' => 'authentication', 'color' => '#EF4444', 'is_featured' => false],
            ['name' => 'Authorization', 'slug' => 'authorization', 'color' => '#F97316', 'is_featured' => false],
            ['name' => 'Caching', 'slug' => 'caching', 'color' => '#10B981', 'is_featured' => false],
            ['name' => 'Microservices', 'slug' => 'microservices', 'color' => '#8B5CF6', 'is_featured' => false],
            ['name' => 'Serverless', 'slug' => 'serverless', 'color' => '#FF9900', 'is_featured' => false],
            ['name' => 'JAMstack', 'slug' => 'jamstack', 'color' => '#F0047F', 'is_featured' => false],
        ];

        foreach ($tags as $tagData) {
            Tag::firstOrCreate(
                ['slug' => $tagData['slug']],
                $tagData
            );
        }
    }
}
