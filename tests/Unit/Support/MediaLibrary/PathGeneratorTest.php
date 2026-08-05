<?php

declare(strict_types=1);

use App\Models\UserProfile;
use App\Support\MediaLibrary\PathGenerator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

describe('MediaLibrary PathGenerator', function (): void {

    it('generates correct base path', function (): void {
        $media = createMediaMock(123, UserProfile::class);

        config()->set('media-library.prefix', 'uploads');

        $pathGenerator = new PathGenerator;
        $basePath = $pathGenerator->getPath($media);

        expect($basePath)->toBe('uploads/UserProfile/123/');
    });

    it('generates correct conversions path', function (): void {
        $media = createMediaMock(456, UserProfile::class);

        config()->set('media-library.prefix', 'media');

        $pathGenerator = new PathGenerator;
        $conversionPath = $pathGenerator->getPathForConversions($media);

        expect($conversionPath)->toBe('media/UserProfile/456/conversions/');
    });

    it('generates correct responsive images path', function (): void {
        $media = createMediaMock(789, UserProfile::class);

        config()->set('media-library.prefix', 'storage');

        $pathGenerator = new PathGenerator;
        $responsivePath = $pathGenerator->getPathForResponsiveImages($media);

        expect($responsivePath)->toBe('storage/UserProfile/789/responsive-images/');
    });

    it('handles empty prefix correctly', function (): void {
        $media = createMediaMock(100, UserProfile::class);

        config()->set('media-library.prefix', '');

        $pathGenerator = new PathGenerator;
        $basePath = $pathGenerator->getPath($media);

        expect($basePath)->toBe('UserProfile/100/');
    });

    it('resolves an enforced morph map alias back to the FQCN basename', function (): void {
        $media = createMediaMock(321, 'user_profile');

        config()->set('media-library.prefix', 'uploads');

        $pathGenerator = new PathGenerator;
        $basePath = $pathGenerator->getPath($media);

        expect($basePath)->toBe('uploads/UserProfile/321/');
    });

    function createMediaMock(int $id, string $modelType): Media
    {
        $media = Mockery::mock(Media::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $media
            ->shouldReceive('getKey')->andReturn($id)
            ->shouldReceive('getAttribute')->with('model_type')->andReturn($modelType)
            ->shouldReceive('getTable')->andReturn('media');

        $media->exists = true;
        $media->wasRecentlyCreated = false;

        return $media;
    }
});
