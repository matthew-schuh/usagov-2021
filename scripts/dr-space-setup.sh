#!/bin/sh

#####################################################################
##
## This is meant to be a temporary script to assist in setting up
## the DR spaces for testing USAGOV-1083 w/ non-standard DNS records
##
#####################################################################

WWW_APP=www
WAF_APP=waf-dr
CMS_APP=cms
ORG=gsa-tts-usagov
APP_SPACE=dev-dr
EGRESS_SPACE=shared-egress-dr

#echo=echo

ATAG=8244
CTAG=$ATAG
WTAG=$ATAG
STAG=$ATAG
export CDIGEST=@sha256:496ae1b256c7082e7df1235f05274397e7f49070ac29ee22f3b313201c8b0b09
export WDIGEST=@sha256:49cb15b6bbbc2873ea8952802094f938bbd8c99310491c91f381b664516c1857
export SDIGEST=@sha256:87d84e1b041763906b3e21649e372a5ff830c7f33531fbcb66ac7fd8b60a3c79

#echo  cf delete-space $APP_SPACE
#while [ 1 = 1 ]; do clear; $echo cf delete-space $APP_SPACE; sleep 10; done
#exit

#echo  cf delete-space $EGRESS_SPACE
#$echo cf delete-space $EGRESS_SPACE
#exit

#echo bin/cloudgov/create-egress-space $EGRESS_SPACE $ORG  PIPE tee ce.org
#$echo bin/cloudgov/create-egress-space $EGRESS_SPACE $ORG | tee ce.log
#exit

#echo bin/cloudgov/create-app-space $APP_SPACE $ORG PIPE tee ca.log
#$echo bin/cloudgov/create-app-space $APP_SPACE $ORG | tee ca.log
#echo cf target -s $APP_SPACE
#$echo cf target -s $APP_SPACE
#exit

#
#echo cf target -s $APP_SPACE
#$echo cf target -s $APP_SPACE
#echo bin/cloudgov/deploy-services  PIPE tee ds.log
#$echo bin/cloudgov/deploy-services  | tee ds.log
#exit

#
#echo cf target -s $EGRESS_SPACE
#$echo cf target -s $EGRESS_SPACE
#echo cf create-service s3 basic-sandbox key-value  PIPE tee cskv.log
#$echo cf create-service s3 basic-sandbox key-value  | tee cskv.log
#exit

#
#echo cf target -s $APP_SPACE
#$echo cf target -s $APP_SPACE
#echo  bin/cloudgov/create-service-account PIPE tee csa.log
#$echo bin/cloudgov/create-service-account | tee csa.log
#exit

#
#echo  cf target -s $APP_SPACE
#$echo cf target -s $APP_SPACE
#echo  cf delete-service ${APP_SPACE}-usagov-domain
#$echo cf delete-service ${APP_SPACE}-usagov-domain
#exit
#

#echo  cf target -s $APP_SPACE
#$echo cf target -s $APP_SPACE
#echo  cf create-service external-domain domain ${APP_SPACE}-usagov-domain -c '{"domains": "dev-dr.usa.gov,shared-egress-dr.usa.gov"}'
#$echo cf create-service external-domain domain ${APP_SPACE}-usagov-domain -c '{"domains": "dev-dr.usa.gov,shared-egress-dr.usa.gov"}'
#while [ 1 = 1 ]; do clear; cf service ${APP_SPACE}-usagov-domain; sleep 10; done
#exit

##
## why is this being run again??
##
#echo  cf target -s $APP_SPACE
#$echo cf target -s $APP_SPACE
#echo  bin/cloudgov/create-service-account PIPE tee csa2.log
#$echo bin/cloudgov/create-service-account | tee csa2.log
#exit

#
#echo  cf target -s $APP_SPACE
#$echo cf target -s $APP_SPACE
#bin/cloudgov/deploy-cms $CTAG $CDIGEST
#exit

#
#echo  cf target -s $APP_SPACE
#$echo cf target -s $APP_SPACE
#bin/cloudgov/deploy-www $STAG $SDIGEST
#exit

#ROUTE_SERVICE_APP_NAME=$WAF_APP \
#ROUTE_SERVICE_NAME=waf-route-${APP_SPACE}-usagov \
#PROTECTED_APP_NAMES="$CMS_APP,$WWW_APP" \
#bin/cloudgov/deploy-waf $WTAG $WDIGEST
#exit

#cf set-env $WAF_APP IP_ALLOW_ALL_CMS 1
#cf set-env $WAF_APP IP_ALLOW_ALL_WWW 1
#cf restage $WAF_APP

###
cat <<'ZZ'
### Run this on the CMS app, to test the connection to RDS
#!/bin/sh
DB_NAME=$(echo $VCAP_SERVICES | jq -r '.["aws-rds"][] | .credentials.db_name')
DB_NAME=$(echo $VCAP_SERVICES | jq -r '.["aws-rds"][] | .credentials.db_name')
DB_USER=$(echo $VCAP_SERVICES | jq -r '.["aws-rds"][] | .credentials.username')
DB_PW=$(echo $VCAP_SERVICES | jq -r '.["aws-rds"][] | .credentials.password')
DB_HOST=$(echo $VCAP_SERVICES | jq -r '.["aws-rds"][] | .credentials.host')
DB_PORT=$(echo $VCAP_SERVICES | jq -r '.["aws-rds"][] | .credentials.port')
mysql --protocol=TCP -h$DB_HOST -P$DB_PORT -u$DB_USER -p$DB_PW $DB_NAME
ZZ
exit

#PREFIX=usagov-${DEPLOY_TAG}-${SPACE}
SQL_FILE=usagov.sql

#echo "Attempting to deploy database backup $SQL_FILE to $APP_SPACE"
#$echo cat $SQL_FILE | cf ssh cms -c "cat - > /tmp/$SQL_FILE"
#cf ssh $CMS_APP -c "if [ -f /tmp/$SQL_FILE ]; then . /etc/profile; drush sql-drop -y; cat /tmp/$SQL_FILE | drush sql-cli; drush cr; fi"
