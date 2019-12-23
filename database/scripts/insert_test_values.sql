use test;

insert into db (schema_version) values (0);

insert into hosts (id, mac_address, name) values (0, '0001040cdf0c', 'host1');
insert into hosts (id, mac_address, name) values (0, '0001040cdf0d', 'host2');
insert into hosts (id, mac_address, name) values (0, '0001040cdf4c', 'host3');
insert into hosts (id, mac_address, name) values (0, '0001041cdf0c', 'host4');

insert into groups values(0, "group1");
insert into groups values(0, "group2");
