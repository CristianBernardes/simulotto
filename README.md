# Simulotto

**Simulotto** é um sistema Laravel desenvolvido exclusivamente para fins didáticos e educativos, com o objetivo de demonstrar como funcionam sistemas de apostas, auditoria de dados e integridade de informações em ambientes controlados. **Este projeto não possui qualquer ligação com sistemas oficiais de loteria, tampouco simula ou representa funcionalidades de instituições financeiras ou governamentais.**

## 🌐 Tecnologias Utilizadas

-   PHP 8.4 (FPM Alpine)
-   Laravel 12
-   Docker
-   Docker Compose
-   NGINX (Alpine)
-   PostgreSQL 15 (auditoria)

## 💡 Objetivo

Demonstrar na prática como:

-   Criar um ambiente Laravel com dois bancos de dados
-   Trabalhar com auditoria e logs imutáveis
-   Utilizar containers para fins educacionais e simulações técnicas

## 🌟 Requisitos Mínimos

-   Git
-   Docker e Docker Compose instalados
-   PHP CLI instalado (recomendado: PHP 8.2+)

### Linux

-   Distribuição com suporte a Docker (Ubuntu, Debian, Arch, etc.)

### Windows

-   **WSL 2** instalado (preferencialmente com Ubuntu)
-   Docker Desktop com suporte a WSL 2 ativado

## 🚀 Passos para Executar

### 1. Clone o repositório

```bash
git clone https://github.com/CristianBernardes/simulotto.git
cd simulotto
```

### 2. Crie o arquivo `.env`

Copie o `.env.example` e configure:

```bash
cp .env.example .env
```

> As variáveis para conexões com MySQL e PostgreSQL já estão configuradas para os containers.

### 3. Suba os containers

```bash
docker-compose up -d --build
```

Esse comando:

-   Faz o build do container Laravel
-   Sobe MySQL com logs ativados e replicação
-   Sobe PostgreSQL com logs configurados
-   Inicia o NGINX na porta `8080`

### 4. Acesse o sistema

Abra o navegador e acesse:

```
http://localhost:8080
```

---

## ⚠️ Aviso Legal

Este sistema é de uso **estritamente educacional**. O autor não se responsabiliza por qualquer uso indevido deste projeto. Nenhuma funcionalidade aqui apresentada deve ser utilizada em ambientes de produção real sem adaptação e auditoria profissional.

---

No próximo passo, ensinaremos como estruturar os testes unitários, conexões com bancos simultâneos, e a criação de logs de auditoria em PostgreSQL com hash de integridade.
