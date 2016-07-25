use api_db;

create table cart(
  id int primary key auto_increment,
  created datetime not null default current_timestamp,
  email varchar(40) unique not null,
  password varchar(64) not null,
  user_group int not null default 1,
  validated boolean not null default false
);


create table auth_keys(
  user_id int unique references users(id),
  created datetime not null default current_timestamp,
  duration int not null default 5,
  auth_key varchar(64) unique not null
);
