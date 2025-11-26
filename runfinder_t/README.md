```markdown
# RunFinder (exemplo)

Aplicação PHP + MySQL que demonstra:
- Cadastro e login de corredores e organizadores.
- Criação de eventos com rota rua-a-rua usando Leaflet + Leaflet Routing Machine (OSRM).
- Busca por endereço via Nominatim.
- Mapa com tema escuro (CartoDB Dark Matter, aproximando "RunFinder Dark+").
- Inscrições de corredores com código gerado.

Instalação rápida:
1. Crie o banco: CREATE DATABASE runfinder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
2. Importe db.sql no MySQL.
3. Ajuste includes/config.php com credenciais do DB.
4. Coloque os arquivos em um servidor PHP (Apache / Nginx + PHP-FPM).
5. Habilite o Event Scheduler do MySQL para atualização automática de status (opcional):
   SET GLOBAL event_scheduler = ON;
   (ou gerencie status via aplicação — já existe atualização em index.php)
6. Abra no navegador: /index.php

Observações e melhorias recomendadas:
- Em produção, force HTTPS, cookies seguros, e headers de segurança.
- Integrar gateway de pagamento para confirmação de inscrição (campo paid).
- Melhor tratamento de erros e validações: sanitização e limites.
- Suporte a upload de rotas (GPX/GeoJSON) e armazenar rota em formato geográfico (MySQL Spatial).
- Preparar rotas e tile usage para limites de requisitos de uso de serviços públicos (Nominatim/OSRM).
```