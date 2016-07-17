drop database if exists api_db;
create database api_db;
use api_db;

create table users(
  id int primary key auto_increment,
  created datetime not null default current_timestamp,
  email varchar(40) unique not null,
  password varchar(64) not null,
  user_group int not null default 1
);


create table auth_keys(
  user_id int unique references users(id),
  created datetime not null default current_timestamp,
  auth_key varchar(64) not null
);
