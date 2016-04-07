CREATE TABLE mappe
(
  id_map serial NOT NULL,
  name_map text,
  approve boolean,
  enabled boolean,
  umap_id integer,
  def boolean,
  private boolean,
  author text,
  password text,
  mymap boolean,
  desc_mappa text,
  CONSTRAINT id_pk_map PRIMARY KEY (id_map)
);

CREATE TABLE segnalazioni
(
  iduser text,
  bot_request_message text NOT NULL,
  text_msg text,
  file_id text,
  file_type text,
  file_path text,
  lat double precision,
  lng double precision,
  geom geometry,
  state integer,
  id serial NOT NULL,
  data_time timestamp without time zone,
  map integer,
  CONSTRAINT id_pk PRIMARY KEY (id)
);

CREATE TABLE stato
(
  id integer NOT NULL,
  stato text,
  CONSTRAINT id_stato_pk PRIMARY KEY (id)
);

INSERT INTO stato(id, stato) VALUES (0, 'in inserimento');
INSERT INTO stato(id, stato) VALUES (1, 'registrata');
INSERT INTO stato(id, stato) VALUES (2, 'accettata');
INSERT INTO stato(id, stato) VALUES (3, 'respinta');
INSERT INTO stato(id, stato) VALUES (4, 'sospesa');
INSERT INTO stato(id, stato) VALUES (5, 'cancellata');

CREATE TABLE utenti
(
  user_id text NOT NULL,
  type_role text,
  approved boolean,
  map integer,
  alert boolean,
  first_name text,
  last_name text,
  username text,
  CONSTRAINT user_pk PRIMARY KEY (user_id)
);