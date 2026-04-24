use solicitudes;

INSERT INTO rol (nombre)
    VALUES ("Solicitante");
    
INSERT INTO rol (nombre)
    VALUES ("Trabajador");
    
INSERT INTO rol (nombre)
    VALUES ("Administrador");
    
INSERT INTO area (nombre)
    VALUES ("Dirección General");
    
INSERT INTO area (nombre)
    VALUES ("Dirección Académica");
    
INSERT INTO area (nombre)
    VALUES ("Dirección de Vinculación");
    
INSERT INTO area (nombre)
    VALUES ("Docencia");
    
INSERT INTO area (nombre)
    VALUES ("Desarrollo Académico");

INSERT INTO area (nombre)
    VALUES ("Coordinación de Inglés");

INSERT INTO area (nombre)
    VALUES ("Biblioteca");
    
INSERT INTO area (nombre)
    VALUES ("Titulación");
    
INSERT INTO area (nombre)
    VALUES ("Psicopedagogía");
    
INSERT INTO area (nombre)
    VALUES ("Cultura y Deportes");
    
INSERT INTO area (nombre)
    VALUES ("Recursos Materiales");
    
INSERT INTO area (nombre)
    VALUES ("Recursos Financieros");
    
INSERT INTO area (nombre)
    VALUES ("Caja");
    
INSERT INTO area (nombre)
    VALUES ("Planeación");
    
INSERT INTO area (nombre)
    VALUES ("Calidad");
    
INSERT INTO area (nombre)
    VALUES ("Transparencia");
    
INSERT INTO area (nombre)
    VALUES ("Centro de Copiado");
    
INSERT INTO area (nombre)
    VALUES ("Industrial");
    
INSERT INTO area (nombre)
    VALUES ("Innovación Agrícola");
    
INSERT INTO area (nombre)
    VALUES ("Informática");
    
INSERT INTO area (nombre)
    VALUES ("Sistemas Computacionales");
    
INSERT INTO area (nombre)
    VALUES ("Gestión Empresarial");
    
INSERT INTO estado_solicitud (nombre)
    VALUES ("Pendiente");
    
INSERT INTO estado_solicitud (nombre)
    VALUES ("En Proceso");
    
INSERT INTO estado_solicitud (nombre) 
	VALUES ('En Revisión');
    
INSERT INTO estado_solicitud (nombre)
    VALUES ("Finalizada");
    
SELECT * from estado_solicitud;
SELECT * from rol;
SELECT * from area;