//1.download and install PuTTY 

https://www.openshift.com/developers/install-and-setup-putty-ssh-client-for-windows

//2.sh command

//copy the csv files to postgresDB data directory;  shell console under PuTTY

cp ./app-deployments/201*/repo/*.txt /var/lib/openshift/53a180cde0b8cd726e000444/postgresql/data



//3.Connect to postgres sql using PuTTy:
psql -h $OPENSHIFT_POSTGRESQL_DB_HOST -p $OPENSHIFT_POSTGRESQL_DB_PORT -U adminfw7v28h cellwherepsql


//4.psql commandes
#----------------------------------------------
DROP TABLE IF EXISTS map_acc_to_uniprot_loc CASCADE;
CREATE TABLE map_acc_to_uniprot_loc
(
UniprotACC varchar(255),
UniprotID varchar(255),
Species varchar(255),
Localization varchar(255)
); 

COPY map_acc_to_uniprot_loc FROM 'UniprotAccToUniprotLoc_240214.txt';
select count(UniprotACC) from map_acc_to_uniprot_loc;

#-------------------------------------------
DROP TABLE IF EXISTS map_generic_flavour CASCADE;
CREATE TABLE map_generic_flavour
(
GO_id_or_uniprot_term varchar(255) PRIMARY KEY,
Description varchar(255),
OurLocalization varchar(255),
UniquePriorityNumber integer,
SpatialRelation varchar(255)
); 

COPY map_generic_flavour FROM 'Localization_Generic_Flavour.txt';
select count(GO_id_or_uniprot_term) from map_generic_flavour;


#-------------------------------------------
DROP TABLE IF EXISTS map_muscle_flavour CASCADE;
CREATE TABLE map_muscle_flavour
(
GO_id_or_uniprot_term varchar(255) PRIMARY KEY,
Description varchar(255),
OurLocalization varchar(255),
UniquePriorityNumber integer,
SpatialRelation varchar(255)
); 

COPY map_muscle_flavour FROM 'Localization_Muscle_Flavour.txt';
select count(GO_id_or_uniprot_term) from map_muscle_flavour;

