drop database if exists api_db;
create database api_db;
use api_db;

create table users(
  uid varchar(64) primary key,
  created datetime not null default current_timestamp,
  email varchar(40) unique not null,
  password varchar(64) not null,
  validated boolean not null default false,
  user_group int not null default 1
);


create table auth_keys(
  uid varchar(64) references users(id),
  created datetime not null default current_timestamp,
  expires datetime not null,
  auth_key varchar(64) not null
);
