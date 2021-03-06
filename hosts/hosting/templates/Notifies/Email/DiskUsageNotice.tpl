{*
 *  Copyright © 2014 Alex Keda, for www.host-food.ru
 *}
{assign var=Theme value="Заканчивается место на заказе хостинга" scope=global}
Здравствуйте, {$User.Name|default:'$User.Name'}!

Обращаем ваше внимание, что использование дискового пространства на заказ хостинга {$Order.Login|default:'$Order.Login'} составляет {$Order.Used|default:'$Order.Used'}Mb, при максимально доступному, по вашему тарифному плану {$Order.Limit|default:'$Order.Limit'}Mb.

Для очистки места, вы можете удалить ненужные или неиспользуемые данные - файлы, почту, базы данных и т.п.
Также, вы можете сменить тарифный план на больший, в панели управления хостингом:
http://{$smarty.const.HOST_ID|default:'HOST_ID'}/HostingOrders
нажав соответствующую кнопку (с изображением разводного ключа и шестерёнки) напротив нужного заказа. Пересчёт оставшихся дней будет произведён автоматически.

{if $Order.Used > $Order.Limit}
На данный момент, вы используете больше места, чем допустимо по вашему тарифному плану. В случае непринятия мер к устранению превышения (смена тарифа, удаление ненужных данных) мы будем вынуждены заблокировать доступ на запись к вашему аккаунту хостинга, доступ на запись в базы данных, прекратим принимать и отправлять вашу почту. Также, мы можем заблокировать ваш аккаунт целиком.
{/if}

{if !$MethodSettings.CutSign}
--
{$From.Sign|default:'$From.Sign'}

{/if}

