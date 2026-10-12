PHP 8.5 CI retains the existing TYPO3 support. Public TYPO3 11/12 releases are currently blocked by security advisories.

To run these jobs with a license authorized for extension testing, configure the GitHub Actions repository variable `TYPO3_ELTS_REPOSITORY_URL` with the Composer repository URL supplied by the license provider, and the Actions secret `TYPO3_ELTS_COMPOSER_AUTH` with the Composer auth JSON for that repository. CI does not print credential values. A customer license must be authorized for this use before being configured.

Do not disable Composer security blocking or remove older supported TYPO3 versions to pass CI. Keep the PR as a draft until all retained jobs pass.

The optional `weakbit/fallback-cache` integration remains suggested and documented, but is not required by the existing functional tests. It is not installed as an unconditional development dependency: its published 1.1.0 release excludes PHP 8.5 and cannot be used on PHP 8.1 either. Projects using that optional integration must validate a compatible fallback-cache version separately.

Both old and new TYPO3 Rector ranges are available so development dependencies can resolve for the retained PHP versions. PHPStan remains at `max`; PHPDoc types describe the expected external and cached metric data, while `treatPhpDocTypesAsCertain: false` preserves the existing defensive runtime assertions without declaring those assertions redundant solely because of documentation. No baseline errors or test skips were added.
