CREATE TABLE public."AccessRules"
(
  code text NOT NULL,
  data jsonb,
  CONSTRAINT "AccessRules_pkey" PRIMARY KEY (code)
);

CREATE TABLE public."Accounts"
(
  "accountId" text NOT NULL,
  "accountName" text NOT NULL,
  "displayName" text NOT NULL,
  "accountType" text DEFAULT 'user'::text,
  "emailAddress" text NOT NULL,
  enabled boolean DEFAULT true,
  password text,
  "passwordChangeRequired" boolean DEFAULT true,
  "passwordLastChange" timestamp without time zone,
  locked boolean DEFAULT false,
  "lockDate" timestamp without time zone,
  "badPasswordCount" integer,
  "lastLogin" timestamp without time zone,
  "lastIp" text,
  "replacingUserAccountId" text,
  "firstName" text,
  "lastName" text,
  title text,
  salt text,
  "tokenDate" timestamp without time zone,
  CONSTRAINT account_pkey PRIMARY KEY ("accountId"),
  CONSTRAINT "account_accountName_key" UNIQUE ("accountName")
);

CREATE TABLE public."Roles"
(
  "roleId" text NOT NULL,
  "roleName" text NOT NULL,
  description text,
  enabled boolean DEFAULT true,
  CONSTRAINT role_pkey PRIMARY KEY ("roleId")
);

CREATE TABLE public."RolesMembers"
(
  "roleId" text,
  "userAccountId" text NOT NULL,
  CONSTRAINT "roleMember_roleId_fkey" FOREIGN KEY ("roleId")
      REFERENCES public."Roles" ("roleId") MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "roleMember_userAccountId_fkey" FOREIGN KEY ("userAccountId")
      REFERENCES public."Accounts" ("accountId") MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "roleMember_roleId_userAccountId_key" UNIQUE ("roleId", "userAccountId")
);

CREATE TABLE public."EventsFormat"
(
  type text NOT NULL,
  format text NOT NULL,
  message text NOT NULL,
  notification boolean DEFAULT false,
  CONSTRAINT "eventFormat_pkey" PRIMARY KEY (type)
);

CREATE TABLE public."Events"
(
  "eventId" text NOT NULL,
  "eventType" text NOT NULL,
  "timestamp" timestamp without time zone NOT NULL,
  "instanceName" text NOT NULL,
  "orgRegNumber" text,
  "orgUnitRegNumber" text,
  "accountId" text,
  "objectClass" text NOT NULL,
  "objectId" text NOT NULL,
  "operationResult" boolean,
  description text,
  "eventInfo" text,
  CONSTRAINT event_pkey PRIMARY KEY ("eventId")
);

CREATE TABLE public."Contacts"
(
  "contactId" text NOT NULL,
  "contactType" text NOT NULL DEFAULT 'person'::text,
  "orgName" text,
  "firstName" text,
  "lastName" text,
  title text,
  function text,
  service text,
  "displayName" text,
  CONSTRAINT contact_pkey PRIMARY KEY ("contactId")
);

CREATE TABLE public."CommunicationsMean"
(
  code text NOT NULL,
  name text NOT NULL,
  enabled boolean,
  CONSTRAINT "communicationMean_pkey" PRIMARY KEY (code),
  CONSTRAINT "communicationMean_name_key" UNIQUE (name)
);

CREATE TABLE public."Communications"
(
  "communicationId" text NOT NULL,
  "contactId" text NOT NULL,
  purpose text NOT NULL,
  "comMeanCode" text NOT NULL,
  value text NOT NULL,
  info text,
  CONSTRAINT communication_pkey PRIMARY KEY ("communicationId"),
  CONSTRAINT "communication_comMeanCode_fkey" FOREIGN KEY ("comMeanCode")
      REFERENCES public."CommunicationsMean" (code) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "communication_contactId_fkey" FOREIGN KEY ("contactId")
      REFERENCES public."Contacts" ("contactId") MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "communication_contactId_purpose_comMeanCode_key" UNIQUE ("contactId", purpose, "comMeanCode")
);

CREATE TABLE public."Addresses"
(
  "addressId" text NOT NULL,
  "contactId" text NOT NULL,
  purpose text NOT NULL,
  room text,
  floor text,
  building text,
  "number" text,
  street text,
  "postBox" text,
  block text,
  "citySubDivision" text,
  "postCode" text,
  city text,
  country text,
  CONSTRAINT address_pkey PRIMARY KEY ("addressId"),
  CONSTRAINT "address_contactId_fkey" FOREIGN KEY ("contactId")
      REFERENCES public."Contacts" ("contactId") MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "address_contactId_purpose_key" UNIQUE ("contactId", purpose)
);

CREATE TABLE "ServicesPrivilege"
(
  "accountId" text,
  "serviceURI" text,
  CONSTRAINT "servicePrivilege_accountId_fkey" FOREIGN KEY ("accountId")
      REFERENCES "Accounts" ("accountId") MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT "servicePrivilege_accountId_serviceURI_key" UNIQUE ("accountId", "serviceURI")
);