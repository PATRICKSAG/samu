CREATE DATABASE bd_samu;

USE bd_samu;

CREATE TABLE categoria (
   idCategoria INT PRIMARY KEY AUTO_INCREMENT,
   nombre VARCHAR(255) NOT NULL,
   activo TINYINT NOT NULL DEFAULT 1,
   fechaCreacion TIMESTAMP NULL DEFAULT NULL,
   fechaModificacion TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE situacion_establecimiento (
   idSituacionEstablecimiento INT PRIMARY KEY AUTO_INCREMENT,
   nombre VARCHAR(255) NOT NULL,
   activo TINYINT NOT NULL DEFAULT 1,
   fechaCreacion TIMESTAMP NULL DEFAULT NULL,
   fechaModificacion TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE situacion_digemid (
   idSituacionDigemid INT PRIMARY KEY AUTO_INCREMENT,
   nombre VARCHAR(255) NOT NULL,
   activo TINYINT NOT NULL DEFAULT 1,
   fechaCreacion TIMESTAMP NULL DEFAULT NULL,
   fechaModificacion TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE departamento (
   idDepartamento INT PRIMARY KEY AUTO_INCREMENT,
   nombre VARCHAR(255) NOT NULL,
   activo TINYINT NOT NULL DEFAULT 1,
   fechaCreacion TIMESTAMP NULL DEFAULT NULL,
   fechaModificacion TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE provincia (
   idProvincia INT PRIMARY KEY AUTO_INCREMENT,
   nombre VARCHAR(255) NOT NULL,
   idDepartamento INT NOT NULL,
   activo TINYINT NOT NULL DEFAULT 1,
   FOREIGN KEY (idDepartamento) 
	REFERENCES departamento(idDepartamento),
   fechaCreacion TIMESTAMP NULL DEFAULT NULL,
   fechaModificacion TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE distrito (
   idDistrito INT PRIMARY KEY AUTO_INCREMENT,
   nombre VARCHAR(255) NOT NULL,
   idProvincia INT NOT NULL,
   activo TINYINT NOT NULL DEFAULT 1,
   FOREIGN KEY (idProvincia) 
	REFERENCES provincia(idProvincia),
   fechaCreacion TIMESTAMP NULL DEFAULT NULL,
   fechaModificacion TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE establecimiento (
	idEstablecimiento BIGINT PRIMARY KEY AUTO_INCREMENT,
	ruc VARCHAR(11) UNIQUE NOT NULL,
	razonSocial VARCHAR(500) NOT NULL,
	responsableLegal VARCHAR(500) NULL DEFAULT NULL,
   cargoRepresentanteLegal VARCHAR(500) NULL DEFAULT NULL,
	informal TINYINT NOT NULL DEFAULT 0,
	activo TINYINT NOT NULL DEFAULT 1,
   fechaCreacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   fechaModificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
);

CREATE TABLE sede(
   idSede BIGINT PRIMARY KEY AUTO_INCREMENT,
   idEstablecimiento BIGINT,
   nombre VARCHAR(500) NULL DEFAULT NULL,
   numeroEstacion VARCHAR(20) NULL DEFAULT NULL,
   fechaRegistroSi DATE NULL DEFAULT NULL COMMENT 'Fecha de regesitro en el SI-DIGEMID',
	idCategoria INT NULL DEFAULT NULL,
   idSituacionEstablecimiento INT NULL DEFAULT NULL,
   idSituacionDigemid INT NULL DEFAULT NULL,
	direccion VARCHAR(1000) NULL DEFAULT NULL,
	telefono VARCHAR(15) NULL DEFAULT NULL,
   -- estadoAbiertoCerrado ENUM('abierto', 'cerrado','anulado') DEFAULT 'abierto',
	tieneQuimicoFarmaceutico TINYINT NOT NULL DEFAULT 0,
	idDepartamento INT NULL DEFAULT NULL,
	idProvincia INT NULL DEFAULT NULL,
	idDistrito INT NULL DEFAULT NULL,
	horarioFuncionamiento VARCHAR(255) NULL DEFAULT NULL,
	activo TINYINT NOT NULL DEFAULT 1,
	FOREIGN KEY (idEstablecimiento) REFERENCES establecimiento(idEstablecimiento),
   FOREIGN KEY (idCategoria) REFERENCES categoria(idCategoria),
   FOREIGN KEY (idSituacionEstablecimiento) REFERENCES situacion_establecimiento(idSituacionEstablecimiento),
   FOREIGN KEY (idSituacionDigemid) REFERENCES situacion_digemid(idSituacionDigemid),
	FOREIGN KEY (idDepartamento) REFERENCES departamento(idDepartamento),
	FOREIGN KEY (idProvincia) REFERENCES provincia(idProvincia),
	FOREIGN KEY (idDistrito) REFERENCES distrito(idDistrito),
   fechaCreacion  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   fechaModificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
);

CREATE TABLE personaldeIspeccion(
   idPersonal BIGINT PRIMARY KEY AUTO_INCREMENT,
   nombreEncargado VARCHAR(500) NULL DEFAULT NULL,
   apellidosEncargado VARCHAR(500) NULL DEFAULT NULL,
   descripcion VARCHAR(500) NULL DEFAULT NULL,
   fechaCreacion  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   fechaModificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE expediente (
   idExpediente INT PRIMARY KEY AUTO_INCREMENT,
   idSede INT NOT NULL,
   codigoInterno VARCHAR(255) NULL DEFAULT NULL,
   judicializado TEXT NULL DEFAULT NULL,
   responsable TEXT NULL DEFAULT NULL,
   observacion TEXT NULL DEFAULT NULL,
   numeroFolios VARCHAR(255) NULL DEFAULT NULL,
   numeroActa VARCHAR(255) NULL DEFAULT NULL,
   tipodeInspeccion VARCHAR (255)  NULL DEFAULT NULL,
   fechaInspeccion DATE NULL DEFAULT NULL,
   informeTecnicoInspeccion TEXT NULL,
   informeTecnicoInspeccionFecha DATE NULL DEFAULT NULL,
   certificadoBuenasPracticas TEXT NULL,
   certificadoBuenasPracticasFechaInicio DATE NULL,
   certificadoBuenasPracticasFechaFin DATE NULL,
   fechaDescargoAdministrado DATE NULL,
   -- MEDIDA DE SEGURIDAD
   msRegistroCierreTemporal VARCHAR(255) NULL,
   msRegistroCierreTemporalFecha DATE NULL,
   msDescargoApelacion TEXT NULL,
   msDescargoApelacionFecha DATE NULL,
   msRegistroLevantamientoCierre TEXT NULL,
   msRegistroLevantamientoCierreFecha DATE NULL,

   ---fase instructora
   msInformeTecnicoInicioPas TEXT NULL,
   msInformeTecnicoInicioPasFecha DATE NULL,
   msInformeTecnicoNuevoInicioPas TEXT NULL,
   msInformeTecnicoNuevoInicioPasFecha DATE NULL,
   -- Fase Instructora
   fiOficioInicioPas TEXT NULL,
   fiOficioInicioPasFechaNotificacion DATE NULL,
   fiFechaDescargo5Dias DATE NULL,
   fiCaducidadOficio TEXT NULL,
   fiCaducidadFecha DATE NULL,
   fiOficioElevaResolverNulidad TEXT NULL,
   fiOficioElevaResolverNulidadFecha DATE NULL,
   fiRespuestaNulidad TEXT NULL,
   fiRespuestaNulidadFecha DATE NULL,
   -- Fase Sancionadora
   fsInformeFinalQf TEXT NULL,
   fsInformeFinalQfFecha DATE NULL,
   fsOficioEmitidoGeresaSgrs TEXT NULL,
   fsOficioEmitidoGeresaSgrsFechaNotificacion DATE,
   fsFechaDescargo5Dias TEXT NULL,
   fsOficioElevaResolverNulidad TEXT NULL,
   fsOficioElevaResolverNulidadFecha DATE NULL,
   fsRespuestaNulidad TEXT NULL,
	fsRespuestaNulidadFecha DATE NULL,
   fechaCreacion  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   fechaModificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (idPersonal) REFERENCES personaldeIspeccion(idPersonal),
);


