DROP TABLE IF EXISTS log CASCADE;
DROP TABLE IF EXISTS configuration CASCADE;
DROP TABLE IF EXISTS reports CASCADE;
DROP TABLE IF EXISTS groups CASCADE;
DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE "log"
(
  id serial NOT NULL,
  "action" character varying(50),
  username character varying(50),
  information text,
  created_on timestamp with time zone DEFAULT now(),
  ip character varying(60),
  "system" boolean DEFAULT false,
  CONSTRAINT pk_log PRIMARY KEY (id)
);

CREATE TABLE "configuration"
(
  id serial NOT NULL,
  var_group character varying(50),
  var_name character varying(128),
  var_value character varying(512),
  var_desc text,
  created_on timestamp with time zone DEFAULT now(),
  created_by character varying(50),
  modified_on date,
  modified_by character varying(50),  
  CONSTRAINT pk_configuration PRIMARY KEY (id),
  CONSTRAINT "unique cfg_name" UNIQUE (var_group, var_name)
);

CREATE TABLE "reports"
(
  id serial NOT NULL,
  name character varying(50),
  type character varying(15),
  sql text,
  created_on timestamp with time zone DEFAULT now(),
  created_by character varying(50),
  modified_on date,
  modified_by character varying(50),  
  CONSTRAINT pk_reports PRIMARY KEY (id),
  CONSTRAINT "unique report_name" UNIQUE (name)
);

CREATE TABLE "groups"
(
  id serial NOT NULL,
  name character varying(50),
  description character varying(512),
  created_on timestamp with time zone DEFAULT now(),
  created_by character varying(50),
  modified_on date,
  modified_by character varying(50),  
  CONSTRAINT pk_groups PRIMARY KEY (id),
  CONSTRAINT "unique group_name" UNIQUE (name)
);
insert into groups(name, description) values ('admins', 'built-in admins group');

CREATE TABLE users
(
  username character varying(50) NOT NULL DEFAULT ''::character varying,
  "name" character varying(150) DEFAULT ''::character varying,
  company character varying(150) DEFAULT ''::character varying,
  avatar character varying(150) DEFAULT ''::character varying,
  "password" character varying(100) NOT NULL DEFAULT ''::character varying,
  email character varying(150) DEFAULT ''::character varying,
  id_group integer NOT NULL,
  lastaccess date,
  created_on date,
  created_by character varying(50),
  modified_on date,
  modified_by character varying(50),
  active boolean DEFAULT true,
  CONSTRAINT pk_persona PRIMARY KEY (username),
  CONSTRAINT fk_user_group FOREIGN KEY (id_group) REFERENCES groups(id)
);
insert into users(username, password, id_group) values ('admin', md5('123'), (select id from groups where name = 'admins'));


CREATE TABLE "ap_triggers"
(
  id serial NOT NULL,
  action character varying(50),
  entity character varying(15),
  key  character varying(15),
  command text,
  single_use boolean DEFAULT false,
  created_on timestamp with time zone DEFAULT now(),
  created_by character varying(50),
  modified_on date,
  modified_by character varying(50),  
  CONSTRAINT pk_triggers PRIMARY KEY (id),
  CONSTRAINT "unique triggers" UNIQUE (action, entity, key)
);
