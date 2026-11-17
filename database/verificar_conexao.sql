USE oficina_rota;
SELECT @@hostname AS servidor, @@port AS porta, VERSION() AS versao,
       @@version_comment AS distribuicao, DATABASE() AS banco, CURRENT_USER() AS usuario;
SELECT (SELECT COUNT(*) FROM usuarios) AS usuarios,
       (SELECT COUNT(*) FROM clientes) AS clientes,
       (SELECT COUNT(*) FROM veiculos) AS veiculos,
       (SELECT COUNT(*) FROM ordens) AS ordens;
