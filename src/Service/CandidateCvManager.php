<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CandidateCvManager
{
    public function __construct(
        private readonly string $projectDir,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function hasProfileCv(User $user): bool
    {
        return $user->getCvFilename() !== null && is_file($this->getProfileCvAbsolutePath($user->getCvFilename()));
    }

    public function duplicateProfileCvForApplication(User $user): ?string
    {
        $cvFilename = $user->getCvFilename();
        if ($cvFilename === null) {
            return null;
        }

        $sourcePath = $this->getProfileCvAbsolutePath($cvFilename);
        if (!is_file($sourcePath)) {
            return null;
        }

        $targetDirectory = $this->projectDir.'/public/uploads/candidatures/cv';
        $this->filesystem->mkdir($targetDirectory);

        $pathInfo = pathinfo($cvFilename);
        $baseName = $pathInfo['filename'] ?? 'cv';
        $extension = isset($pathInfo['extension']) ? '.'.$pathInfo['extension'] : '';
        $targetFilename = sprintf('%s-%s%s', $baseName, uniqid('', true), $extension);

        $this->filesystem->copy($sourcePath, $targetDirectory.'/'.$targetFilename, true);

        return $targetFilename;
    }

    public function storeUploadedApplicationCv(UploadedFile $uploadedFile): string
    {
        $targetDirectory = $this->projectDir.'/public/uploads/candidatures/cv';
        $this->filesystem->mkdir($targetDirectory);

        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = preg_replace('/[^A-Za-z0-9_-]+/', '-', $originalFilename) ?: 'cv';
        $safeFilename = trim((string) $safeFilename, '-');
        $extension = $uploadedFile->guessExtension() ?: 'pdf';
        $targetFilename = sprintf('%s-%s.%s', $safeFilename, uniqid('', true), $extension);

        $uploadedFile->move($targetDirectory, $targetFilename);

        return $targetFilename;
    }

    public function getApplicationCvPublicPath(?string $filename): ?string
    {
        if ($filename === null) {
            return null;
        }

        return '/uploads/candidatures/cv/'.$filename;
    }

    public function getApplicationCvAbsolutePath(?string $filename): ?string
    {
        if ($filename === null) {
            return null;
        }

        return $this->projectDir.'/public/uploads/candidatures/cv/'.$filename;
    }

    private function getProfileCvAbsolutePath(string $filename): string
    {
        return $this->projectDir.'/public/uploads/cvs/'.$filename;
    }
}
