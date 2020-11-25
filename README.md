Ctec
==============================

Requirements
------------

- Kanboard >= 1.2.13

Documentation
-------------

- Vá até a pasta plugins do projeto kanboard
- Rode o comando para baixar o plugin

``` bash
git clone https://github.com/canoastec/kanboard-plugin.git

```

- Após baixar renomeie a pasta do plugin para Ctec.
- Entre na pasta e execute

``` bash
composer install
```

``` bash
npm install
```

``` bash
npm run dev
```

- Renomeie o arquivo .env.example para .env, dentro deste arquivo possui a seguinte estrutura:

``` bash
PROJECT_ID="ID DO PROJECT KANBOARD"
QUERY_CURRENT_SPRINT="QUERY FOR SEARCH SPRINT CURRENT"
GESTAOSISTEMAS_API="LINK API"
```

- Substitua o **"ID DO PROJECT KANBOARD"**, pelo o id do projeto da onde quer tirar as informações.

- Substitua o **"QUERY FOR SEARCH SPRINT CURRENT"**, pela query de busca de tarefas do kanboard. Exemplo:
  
``` bash 
QUERY_CURRENT_SPRINT="column:Andamento" 
```

- E execute o kanboard normalmente, o gráfico gerado pelo plugin pode ser visto na página inicial do kanboard, no menu lateral, menu **Grafico estimado x executado**

