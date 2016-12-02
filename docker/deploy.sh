#!/bin/bash

usage () {
    echo
    echo "Usage: $0 <prod|staging> [local]"
    echo
}

die () {
    echo
    echo "ERROR: $1"
    echo
    exit 1
}

if [ $# -eq 0 ]; then usage; exit 1; fi

case "$1" in
    prod) BUILD_ENV="prod" ;;
    staging) BUILD_ENV="staging" ;;
    *) usage && die "Unknow argument!" ;;
esac

unset SUFFIX
unset PREFIX
if [ $BUILD_ENV = "staging" ]; then SUFFIX="_staging"; PREFIX="staging_"; fi

echo "Deploying latest version to $BUILD_ENV"
if [ "$2" = "local" ]; then unset DOCKER_HOST; else export DOCKER_HOST=tcp://swarm-master.aws.snaprapid.local:3375; fi
if [ $DOCKER_HOST ]; then echo "Connecting to SnapRpaid swarm cluster..."; else echo "Connecting to local docker-engine..."; fi
docker info >/dev/null 2>&1 && echo "Connected!" || die "No connection to Swarm"

echo "Pulling latest containers onto cluster..."
echo "  dashboards_api:$BUILD_ENV.latest" && docker pull registry.snaprapid.com/dashboards_api:$BUILD_ENV.latest >/dev/null 2>&1 || die "image not downloaded!"
echo "  dashboards_backend_php:$BUILD_ENV.latest" && docker pull registry.snaprapid.com/dashboards_backend_php:$BUILD_ENV.latest >/dev/null 2>&1 || die "image not downloaded!"
echo "  dashboards_api_src:$BUILD_ENV.latest" && docker pull registry.snaprapid.com/dashboards_api_src:$BUILD_ENV.latest >/dev/null 2>&1 || die "image not downloaded!"

echo "Stopping existing containers..."
docker stop dash_api_src${SUFFIX} >/dev/null 2>&1
docker rm -f dash_api_src${SUFFIX} >/dev/null 2>&1
docker stop dash_api_php${SUFFIX} >/dev/null 2>&1
docker rm -f dash_api_php${SUFFIX} >/dev/null 2>&1
docker stop dash_api_web${SUFFIX} >/dev/null 2>&1
docker rm -f dash_api_web${SUFFIX} >/dev/null 2>&1
echo "Done."

echo "Deploying new containers..."
echo "  dash_api_src${SUFFIX}" && docker run -d -v /var/www/symfony -e constraint:subnet==public -e constraint:gpu_node==false --name dash_api_src${SUFFIX} registry.snaprapid.com/dashboards_api_src:$BUILD_ENV.latest >/dev/null 2>&1 || die "container not started!"
echo "  dash_api_php${SUFFIX}" && docker run -d --volumes-from dash_api_src${SUFFIX} -e constraint:subnet==public -e constraint:gpu_node==false --name dash_api_php${SUFFIX} registry.snaprapid.com/dashboards_backend_php:$BUILD_ENV.latest >/dev/null 2>&1 || die "container not started!"
echo "  dash_api_web${SUFFIX}" && docker run -d --volumes-from dash_api_src${SUFFIX} -P --link dash_api_php${SUFFIX}:php -e constraint:subnet==public -e constraint:gpu_node==false -e SERVICE_NAME=${PREFIX}dashboards_api --name dash_api_web${SUFFIX} registry.snaprapid.com/dashboards_api:$BUILD_ENV.latest >/dev/null 2>&1 || die "container not started!"
echo "Done."
unset DOCKER_HOST
echo "Deployment successfull."
