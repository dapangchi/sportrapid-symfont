##Deploy dashboards_api service to SnapRapid Swarm Cluter

Shippable CI/CD produce all necessary docker images for _dashboards_api_ service. It set different docker tags
for images:

- _Staging_ images
```
dashboards_api:staging.latest
dashboards_api_src:staging.latest
dashboards_backnd_php:staging.latest
```
- _Prod_ images
```
dashboards_api:prod.latest
dashboards_api_src:prod.latest
dashboards_backnd_php:prod.latest
```

There are images with build number in private docker registry too, as example `dashboards_api:staging.45` means
№45'th build of staging image.

There are two deployment scripts:

- `deploy.sh` to deploy services to SnarRapid Cluster or localhost (for testing) via VPN connction
- `deploy_via_bastion.sh` to deploy services to SnapRpaud Cluster via bastion host (without VPN connect)

###Deploy services to Snaprapid Swarm cluster via VPN or locally by `deploy.sh`.

To see usage info just run scrupt without arguments
```
$ ./deploy.sh

Usage: ./deploy.sh <prod|staging> [local]

```

To deploy staging version of service locally (on you localhost) use:

```
$ ./deploy.sh staging local
Deploying latest version to staging
Connecting to local docker-engine...
Connected!
Pulling latest containers onto cluster...
  dashboards_api:staging.latest
  dashboards_backend_php:staging.latest
  dashboards_api_src:staging.latest
Stopping existing containers...
Done.
Deploying new containers...
  dash_api_src_staging
  dash_api_php_staging
  dash_api_web_staging
Done.
Deployment successfull.
```

To deploy service over Swarm cluster do not use `local` agrument.

>NOTE: to deploy over cluster you must be connected to SnapRapid VPN

Example of deploy prod version over cluster:

```
$ ./deploy.sh prod
Deploying latest version to prod
Connecting to SnapRpaid swarm cluster...
Connected!
Pulling latest containers onto cluster...
  dashboards_api:prod.latest
  dashboards_backend_php:prod.latest
  dashboards_api_src:prod.latest
Stopping existing containers...
Done.
Deploying new containers...
  dash_api_src
  dash_api_php
  dash_api_web
Done.
Deployment successfull.
```

###Deploy services to Snaprapid Swarm cluster via bastion host by `deploy_via_bastion.sh`.

To see usage info just run scrupt without arguments
```
$ ./deploy_via_bastion.sh

Usage: ./deploy_via_bastion.sh <prod|staging>

```

By deafult `deploys_via_bastion.sh` use `bastion.snaprpid.com` as hostname for bastion host and
`~/.ssh/snaprapid_infra.pem` as filename of private SSH key. You can override that values by setting
environment variables `BASTION_HOST` and `BASTION_KEY` (both or one of them). As example use other ssh key:

```
BASTION_KEY=/path/to/my_key ./deploy_via_bastion.sh staging
```

Example of deploy **staging** version over cluster:
```
$ ./deploy_via_bastion.sh staging
Deploying latest version to staging
Connecting to SnapRpaid swarm cluster...
Connected!
Pulling latest containers onto cluster...
  dashboards_api:staging.latest
  dashboards_backend_php:staging.latest
  dashboards_api_src:staging.latest
Stopping existing containers...
Done.
Deploying new containers...
  dash_api_src_staging
  dash_api_php_staging
  dash_api_web_staging
Done.
Deployment successfull.
```
