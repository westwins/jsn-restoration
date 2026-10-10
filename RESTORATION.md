# JSN restoration record

## Provenance

The Jesuit Science Network (JSN) was created by Dagmar Mrozik as a digital prosopography of scholars in the early modern Society of Jesus. Its original [source repository](https://github.com/jesuitsciencenetwork/web) credits the research project and describes the Person Data Repository, the intermediary SQL database, and the original website. This repository retains that project's Git history and MIT license. The starting commit for the restoration is `238a09f1496ef7dbe72e756ef9633f1b566aeb5c` (2021-06-13).

The restoration was deployed by Soo Hwa Lee at [jsn.soohwalee.com](https://jsn.soohwalee.com/). The Oracle Cloud working tree at `/home/ubuntu/jsn-web` was inspected on 2026-10-11. The code here records its Docker configuration and application changes, with deployment credentials removed. The original researchers' data collection and interpretation remain their work.

## Changes observed in the deployed working tree

- A PHP 7.2/Apache image, MariaDB 10.5, and Node 14 build service were added to run the legacy Symfony application in Docker.
- The Google Maps browser and geocoding keys were changed for the restored deployment. This public copy reads them from environment variables; **no deployed key is included**.
- The Maps visualization library was removed and its heatmap object replaced with a no-op. The public copy adds the no-op `getMap` and `setData` methods used elsewhere in the template. The map's marker and cluster views remain the primary spatial interface; the heatmap is unavailable.
- The VIAF RDF endpoint was changed from HTTP to HTTPS.
- A new geocoding cache suffix was used. The deployed code returns coordinates `(0, 0)` when Google geocoding does not return `OK`; this is a **data-quality limitation**, not a verified historical location. Any records produced by this fallback must be identified and corrected before geographical analysis.
- npm build scripts and the lock file were added for asset compilation.

These are observations of the working tree, not claims that the author of this restoration created the original JSN data, model, or interface. The exact dates and AI assistance for individual changes require dated commits or contemporaneous work records before being treated as measured findings.

## Reproduction and limits

1. Review the original [README](README.md), including its link to the upstream release files and SQL dump. The historical database is **not** bundled in this repository.
2. Copy `.env.example` to `.env` and enter a database password and Google Maps keys. Keep `.env` out of Git. Restrict browser and server keys to their intended use.
3. Copy `app/config/parameters.yml.dist` to `app/config/parameters.yml`. Docker Compose supplies database values through the legacy Symfony environment convention and the browser-map key through a runtime environment variable.
4. Install PHP dependencies with Composer 1 in the PHP container, then run the `node` service to build front-end assets.
5. Import the original project's SQL dump into the MariaDB `symfony` database, then start the `app` service. Check the map, at least one person record, source links, and the JSON/XML export.

The stack uses PHP 7.2 and other old dependencies to document the restoration. It requires an upgrade and security review before being treated as a maintained production template. The upstream release, its data terms, and Google Maps terms should be checked separately from the MIT license for the application code.

## Scholarly use

JSN demonstrates a source-linked historical interface in which people, activities, places, and time can be queried together. Restoring its executable form is a distinct contribution in digital preservation and maintenance. It does not make JSN itself an autonomous AI historian. Any later comparison with `silver.soohwalee.com` should cite the original JSN researchers and identify separately which code, data structures, and design ideas were reused or transformed.
