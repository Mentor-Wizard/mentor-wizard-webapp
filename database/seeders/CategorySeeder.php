<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Recursive category tree: every name maps to an array of children.
     * An empty array means the category is a leaf.
     *
     * @var array<string, mixed>
     */
    private array $categories = [
        'Technology' => [
            'Web Development' => [
                'Frontend'        => [],
                'Backend'         => [],
                'Full-Stack APIs' => [],
            ],
            'Mobile Development' => [
                'iOS'            => [],
                'Android'        => [],
                'Cross-Platform' => [],
            ],
            'DevOps & Cloud' => [
                'CI/CD'                   => [],
                'Containers & Kubernetes' => [],
                'Infrastructure as Code'  => [],
                'Cloud Platforms'         => [],
            ],
            'Cybersecurity' => [
                'Application Security' => [],
                'Network Security'     => [],
                'Penetration Testing'  => [],
            ],
            'Game Development' => [
                'Unity'         => [],
                'Unreal Engine' => [],
                'Game Design'   => [],
            ],
        ],
        'Business' => [
            'Marketing' => [
                'Content Marketing'     => [],
                'SEO'                   => [],
                'Social Media'          => [],
                'Performance Marketing' => [],
            ],
            'Finance' => [
                'Personal Finance'  => [],
                'Investing'         => [],
                'Corporate Finance' => [],
            ],
            'Product Management' => [
                'Product Strategy'  => [],
                'Product Discovery' => [],
                'Roadmapping'       => [],
            ],
            'Entrepreneurship' => [
                'Startups'      => [],
                'Fundraising'   => [],
                'Bootstrapping' => [],
            ],
        ],
        'Design' => [
            'UI/UX Design' => [
                'User Research'      => [],
                'Wireframing'        => [],
                'Prototyping'        => [],
                'Interaction Design' => [],
            ],
            'Graphic Design' => [
                'Branding'     => [],
                'Typography'   => [],
                'Print Design' => [],
            ],
            'Motion Design' => [
                '2D Animation'    => [],
                '3D Animation'    => [],
                'Motion Graphics' => [],
            ],
            'Illustration' => [
                'Digital Illustration' => [],
                'Concept Art'          => [],
                'Character Design'     => [],
            ],
        ],
        'Accountancy' => [
            'Corporate'      => [],
            'Small Business' => [],
            'Private'        => [],
        ],
        'Other' => [],
    ];

    public function run(): void
    {
        $this->seedTree($this->categories, null);
    }

    /**
     * @param  array<string, mixed>  $tree
     */
    private function seedTree(array $tree, ?int $parentId): void
    {
        foreach ($tree as $name => $children) {
            $category = Category::factory()->create([
                'name'      => $name,
                'slug'      => Str::slug($name),
                'parent_id' => $parentId,
            ]);

            if ($children !== []) {
                $this->seedTree($children, $category->getKey());
            }
        }
    }
}
