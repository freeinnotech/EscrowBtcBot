# EscrowBtcBot
Make payment securely on telegram using EscrowBtcBot.
# Summary
This enhanced bot now supports negotiation between users and sellers, allowing them to communicate directly within the escrow context. Users can create escrow transactions, check balances, and communicate with their counterparties, all while admins have the tools to manage the user base and roles effectively.
# Admin commands
The admin can use the /allusers command to list all registered users.
The admin can use /setrole <user_id> <role> to set the role of a user (e.g., /setrole 123456789 admin)
# Starting the bot
Start a chat with your bot on Telegram.
Use /start to get the BTC address.
Send BTC to the provided address.
Use /check to verify if the payment was received.
Use /balance to check the current balance.
Use /transactions to list recent transactions.
Use /createescrow <amount> <seller_chat_id> to create an escrow transaction.
Use /myescrows to   list your escrows.
Use /message <chat_id> <message> to send a negotiation message.
# Uploading files
Upload bot.php to your web server.
# File permissions
Ensure that users.json and escrows.json files exist on your server and are writable by the script.
# Bot script configuration
Replace 'YOUR_TELEGRAM_BOT_API_TOKEN' with your Telegram bot API token in bot.php.
Replace 'YOUR_BLUEWALLET_BTC_ADDRESS' with your BlueWallet BTC address in bot.php.
Replace 'YOUR_TELEGRAM_CHAT_ID' with the admin's Telegram chat ID in bot.php.
# Webhook setup
Create a file named set_webhook.php with the content provided above.
Replace 'YOUR_TELEGRAM_BOT_API_TOKEN' with your actual Telegram bot API token.
Replace 'https://yourdomain.com/bot.php' with the actual public URL of your bot.php file.
Upload set_webhook.php to your server and access it via your browser (e.g., https://yourdomain.com/set_webhook.php). This will set the webhook for your bot.
# Instructions for Setup and Deployment
Contact @whitecent or email inndeveloper247@gmail.com
