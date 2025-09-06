<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TagEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MentorTagSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            'Web розробка',
            'Mobile розробка',
            'Desktop розробка',
            'Game Development',
            'Embedded Systems',
            'Data Science',
            'Machine Learning',
            'AI Engineering',
            'DevOps',
            'Cloud Engineering',
            'Cybersecurity',
            'QA / Testing',
            'UI/UX Engineering',
            'Full Stack Development',
            'Backend Development',
            'Frontend Development',

            'JavaScript', 'TypeScript', 'PHP', 'Python', 'Ruby', 'Java', 'Kotlin', 'Swift', 'Objective-C',
            'C', 'C++', 'C#', 'Go', 'Rust', 'Dart', 'Scala', 'Elixir', 'Haskell', 'Lua', 'R', 'MATLAB', 'Perl',

            'HTML5', 'CSS3', 'Sass', 'SCSS', 'TailwindCSS', 'Bootstrap', 'Material UI',
            'React.js', 'Next.js', 'Vue.js', 'Nuxt.js', 'Angular', 'Svelte', 'SolidJS', 'Astro',
            'Redux', 'Zustand', 'Pinia', 'jQuery',

            'Node.js', 'Express.js', 'NestJS', 'Fastify',
            'Laravel', 'Symfony', 'CodeIgniter', 'Yii', 'CakePHP',
            'Django', 'Flask', 'FastAPI', 'Ruby on Rails',
            'Spring', 'Micronaut', 'Ktor', '.NET', 'ASP.NET Core',
            'Fiber', 'Gin', 'Echo', 'Actix', 'Rocket', 'Phoenix',
            'GraphQL', 'gRPC', 'REST API',

            'MySQL', 'PostgreSQL', 'SQLite', 'MariaDB', 'Oracle Database',
            'Microsoft SQL Server', 'MongoDB', 'Redis', 'Cassandra', 'DynamoDB',
            'Firebase Firestore', 'CouchDB', 'Neo4j', 'Elasticsearch',

            'Docker', 'Kubernetes', 'OpenShift', 'Terraform', 'Ansible', 'Jenkins',
            'GitHub Actions', 'GitLab CI/CD',
            'AWS', 'Google Cloud Platform', 'Microsoft Azure', 'DigitalOcean',
            'Heroku', 'Vercel', 'Netlify', 'Cloudflare',

            'Unit Testing', 'Integration Testing', 'End-to-End Testing', 'TDD', 'BDD',
            'Jest', 'Mocha', 'Chai', 'Jasmine', 'Cypress', 'Playwright', 'Selenium',
            'PHPUnit', 'PestPHP', 'PyTest',

            'Git', 'GitHub', 'GitLab', 'Bitbucket',
            'Jira', 'Trello', 'Asana', 'Slack',
            'VS Code', 'PhpStorm', 'IntelliJ IDEA', 'Eclipse', 'Android Studio', 'Xcode',

            'OOP', 'FP', 'MVC', 'MVVM', 'Clean Architecture', 'DDD',
            'Microservices', 'Monolithic Architecture', 'Serverless', 'Event-Driven Architecture',

            'OWASP', 'JWT', 'OAuth2', 'OpenID Connect',
            'HTTPS / SSL / TLS', 'CSRF Protection', 'XSS Protection',
            'SQL Injection Prevention', 'Penetration Testing',

            'React Native', 'Flutter', 'Swift (iOS)', 'Kotlin (Android)',
            'Java (Android)', 'Xamarin', 'Ionic', 'NativeScript',

            'WebSockets', 'WebRTC', 'SEO Basics', 'Accessibility (A11y)',
            'Performance Optimization', 'Caching Strategies',
            'Agile', 'Scrum', 'Kanban',
            'Communication Skills', 'Problem Solving', 'Teamwork',
        ];

        $languages = [
            'English',
            'Українська',
            'Deutsch',
            'Français',
            'Español',
            'Italiano',
            'Português',
            'Polski',
            'Čeština',
            'Slovenčina',
            'Magyar',
            'Română',
            'Български',
            'Ελληνικά',
            'Türkçe',
            'العربية',
            'فارسی',
            'עברית',
            '中文 (简体)',
            '中文 (繁體)',
            '日本語',
            '한국어',
            'हिन्दी',
            'বাংলা',
            'ਪੰਜਾਬੀ',
            'ગુજરાતી',
            'தமிழ்',
            'తెలుగు',
            'മലയാളം',
            'ไทย',
            'Tiếng Việt',
            'Bahasa Indonesia',
            'Bahasa Melayu',
            'Filipino',
            'Swahili',
        ];

        $dataSkill = array_map(fn (string $skill): array => ['tag' => $skill, 'type' => TagEnum::STACK], $skills);
        $dataLanguage = array_map(fn (string $skill): array => ['tag' => $skill, 'type' => TagEnum::LANGUAGE], $languages);

        DB::table('mentor_tags')->insert($dataSkill);
        DB::table('mentor_tags')->insert($dataLanguage);

    }
}
