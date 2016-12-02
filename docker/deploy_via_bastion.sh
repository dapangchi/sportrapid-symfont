#!/bin/bash

BHOST=${BASTION_HOST:-bastion.snaprapid.com}
BKEY=${BASTION_KEY:-~/.ssh/snaprapid_infra.pem}
SWARM_MANAGER="swarm-master.aws.snaprapid.local"

usage () {
    echo
    echo "Usage: $0 <prod|staging>"
    echo
}

die () {
    echo
    echo "ERROR: $1"
    echo
    exit 1
}

bssh () {
ssh -i $BKEY -o StrictHostKeyChecking=no -o ProxyCommand="ssh -i $BKEY  -o StrictHostKeyChecking=no ubuntu@$BHOST -W %h:%p" ubuntu@$SWARM_MANAGER $1
}

if [ $# -eq 0  ]; then usage; exit 1; fi
if [ $# -ne 1  ]; then usage; die "Please set one agrument"; fi

case "$1" in
    prod) BUILD_ENV="prod" ;;
    staging) BUILD_ENV="staging" ;;
    *) usage && die "Unknow agrument!" ;;
esac

unset SUFFIX
unset PREFIX
if [ $BUILD_ENV = "staging" ]; then SUFFIX="_staging"; PREFIX="staging_"; fi

echo "Deploying latest version to $BUILD_ENV"
echo "Using $BHOST as bastion host"
echo "Using $BKEY as SSH key"
export DOCKER_HOST=tcp://swarm-master.aws.snaprapid.local:3375
echo "Connecting to SnapRpaid swarm cluster..."
bssh "docker -H $DOCKER_HOST info >/dev/null 2>&1" && echo "Connected!" || die "No connection to Swarm"

echo "Pulling latest containers onto cluster..."
echo "  dashboards_api:$BUILD_ENV.latest" && bssh "docker -H $DOCKER_HOST pull registry.snaprapid.com/dashboards_api:$BUILD_ENV.latest >/dev/null 2>&1" || die "image not downloaded!"
echo "  dashboards_backend_php:$BUILD_ENV.latest" && bssh "docker -H $DOCKER_HOST pull registry.snaprapid.com/dashboards_backend_php:$BUILD_ENV.latest >/dev/null 2>&1" || die "image not downloaded!"
echo "  dashboards_api_src:$BUILD_ENV.latest" && bssh "docker -H $DOCKER_HOST pull registry.snaprapid.com/dashboards_api_src:$BUILD_ENV.latest >/dev/null 2>&1" || die "image not downloaded!"

echo "Stopping existing containers..."
bssh "docker -H $DOCKER_HOST stop dash_api_src${SUFFIX} >/dev/null 2>&1"
bssh "docker -H $DOCKER_HOST rm -f dash_api_src${SUFFIX} >/dev/null 2>&1"
bssh "docker -H $DOCKER_HOST stop dash_api_php${SUFFIX} >/dev/null 2>&1"
bssh "docker -H $DOCKER_HOST rm -f dash_api_php${SUFFIX} >/dev/null 2>&1"
bssh "docker -H $DOCKER_HOST stop dash_api_web${SUFFIX} >/dev/null 2>&1"
bssh "docker -H $DOCKER_HOST rm -f dash_api_web${SUFFIX} >/dev/null 2>&1"
echo "Done."

echo "Deploying new containers..."
echo "  dash_api_src${SUFFIX}" && bssh "docker -H $DOCKER_HOST run -d -v /var/www/symfony -e constraint:subnet==public -e constraint:gpu_node==false --name dash_api_src${SUFFIX} registry.snaprapid.com/dashboards_api_src:$BUILD_ENV.latest >/dev/null 2>&1" || die "container not started!"
echo "  dash_api_php${SUFFIX}" && bssh "docker -H $DOCKER_HOST run -d --volumes-from dash_api_src${SUFFIX} -e constraint:subnet==public -e constraint:gpu_node==false --name dash_api_php${SUFFIX} registry.snaprapid.com/dashboards_backend_php:$BUILD_ENV.latest >/dev/null 2>&1" || die "container not started!"
echo "  dash_api_web${SUFFIX}" && bssh "docker -H $DOCKER_HOST run -d --volumes-from dash_api_src${SUFFIX} -P --link dash_api_php${SUFFIX}:php -e constraint:subnet==public -e constraint:gpu_node==false -e SERVICE_NAME=${PREFIX}dashboards_api --name dash_api_web${SUFFIX} registry.snaprapid.com/dashboards_api:$BUILD_ENV.latest >/dev/null 2>&1" || die "container not started!"
echo "Done."
unset DOCKER_HOST
echo "Deployment successfull."
