=== Plugin Name ===
Contributors: leobalter
Donate link: http://leobalter.net
Tags: rss, feed, atom, cache
Requires at least: 2.8
Tested up to: 2.9.2
Stable tag: 1

Totally in Portuguese-Br yet, this add a very customizabole rss or atom feed. Made after the standard wp rss plugin.

== Description ==

Considerações iniciais:

*	Totally in Portuguese-Br yet, this plugin adds a very customizabole rss or atom feed. Made after the standard wp rss plugin.
*	Totalmente em Português (Brasil) até o momento. Esse plugin adiciona um feed de rss ou atom totalmente customizável. Feito a partir do plugin de rss padrão do WordPress

Um plugin simples de RSS Feed que é totalmente baseado no widget padrão de RSS do wordpress, porém com diversas configurações a mais.

O plugin está em licença 100% livre, faça o que quiser, só não queira me responsabilizar pelas suas mudanças.

A grande motivação para a criação desse plugin foi algo que muito incomodava eu e meus colegas de equipe no trabalho. O widget padrão de RSS no wordpress tem um cache exclusivo que é renovado obrigatóriamente a pelo menos cada 12 horas. Isso é muito ruim para sites que contém conteúdo novo surgindo a cada minuto. Como é o caso de onde trabalhamos.

A primeira implementação nova a esse plugin foi justamente a temporização do cache, com tempo padrão ajustado para 5 minutos. Não faz sentido haver tempo maior em cache pois existem plugins próprios para esse tipo de funcionalidade.

Outras opções que podem ser encontradas no plugin:

    * Opção de exibir data e hora (o widget padrão de RSS mostrava apenas a data)
    * Opção de exibir essa data e hora antes do título do feed
    * Opção de exibir formato específico para a data e hora (baseados no objeto date() do PHP).

Não deixe de comentar!

== Installation ==

1. Faça o upload o 'superrss.php' ao diretório '/wp-content/plugins/'
1. Ative o plugin através do 'Plugins' menu do Wordpress
1. Pronto. O widget do SuperRSS está pronto para ser utilizado.

== Frequently Asked Questions ==

Para tirar dúvidas em geral, acesse o artigo sobre o plugin em meu blog: http://leobalter.net/blog/2010/03/13/superrss-widget-rss-jeito/

== Changelog ==

= 1.0 =
* Versão estável para primeiro release público

= 0.5 =
* Sem release público, o plugin começou apenas com implementação de mudança de tempo de cache.

== Mais informações ==

Não deixe de visitar [Leo Balter](http://leobalter.net/blog/2010/03/13/superrss-widget-rss-jeito/ "SuperRSS - Leo Balter") para mais informações e para colaborar com meu trabalho (sua visita ao meu site é muito importante).

Grato,
Leo Balter