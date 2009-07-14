DROP TABLE routes;

CREATE TABLE routes
(
airline VARCHAR(2),
alid INT, 
src_ap VARCHAR(3),
src_apid INT,
dst_ap VARCHAR(3),
dst_apid INT,
codeshare TEXT,
stops TEXT,
equipment TEXT,
added VARCHAR(1),
rid INT AUTO_INCREMENT PRIMARY KEY,
UNIQUE INDEX (alid, src_apid, dst_apid),
FOREIGN KEY (src_apid) REFERENCES airports (apid),
FOREIGN KEY (dst_apid) REFERENCES airports (apid),
FOREIGN KEY (alid) REFERENCES airlines (alid)
);

\! echo Filtering out duplicates...

\! uniq -w 10 <routes.dat >routes2.dat

\! echo Importing...

LOAD DATA LOCAL INFILE 'routes2.dat'
INTO TABLE routes
FIELDS TERMINATED BY '\t' OPTIONALLY ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 2 LINES
(airline, src_ap, dst_ap, codeshare, stops, equipment);

UPDATE routes SET codeshare='Y' WHERE codeshare='*';

\! echo Adding src IDs...

UPDATE routes AS r,airports as a SET r.src_apid=a.apid WHERE a.iata=r.src_ap;

\! echo Adding dst IDs...

UPDATE routes AS r,airports as a SET r.dst_apid=a.apid WHERE a.iata=r.dst_ap;

\! echo Adding airline IDs...

UPDATE routes AS r,airlines as l SET r.alid=l.alid WHERE l.iata=r.airline;

\! rm routes2.dat
\! echo Done.
