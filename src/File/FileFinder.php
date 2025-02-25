<?php declare(strict_types = 1);

namespace PHPStan\File;

use Symfony\Component\Finder\Finder;

class FileFinder
{

	private FileExcluder $fileExcluder;

	private FileHelper $fileHelper;

	/** @var string[] */
	private array $fileExtensions;

	/**
	 * @param FileExcluder $fileExcluder
	 * @param FileHelper $fileHelper
	 * @param string[] $fileExtensions
	 */
	public function __construct(
		FileExcluder $fileExcluder,
		FileHelper $fileHelper,
		array $fileExtensions
	)
	{
		$this->fileExcluder = $fileExcluder;
		$this->fileHelper = $fileHelper;
		$this->fileExtensions = $fileExtensions;
	}

	/**
	 * @param string[] $paths
	 * @return FileFinderResult
	 */
	public function findFiles(array $paths): FileFinderResult
	{
		$onlyFiles = true;
		$files = [];
		foreach ($paths as $path) {
			if (is_file($path)) {
				$files[] = $this->fileHelper->normalizePath($path);
			} elseif (!file_exists($path)) {
				throw new \PHPStan\File\PathNotFoundException($path);
			} else {
				$finder = new Finder();
				$finder->followLinks();
				foreach ($finder->files()->name('*.{' . implode(',', $this->fileExtensions) . '}')->in($path) as $fileInfo) {
					$files[] = $this->fileHelper->normalizePath($fileInfo->getPathname());
					$onlyFiles = false;
				}
			}
		}

		$files = array_values(array_filter($files, function (string $file): bool {
			return !$this->fileExcluder->isExcludedFromAnalysing($file);
		}));

		return new FileFinderResult($files, $onlyFiles);
	}

}
