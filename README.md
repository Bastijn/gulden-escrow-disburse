# Gulden escrow disburse

### Summary:
This script checks the balance of the escrow account used to collect miner earnings, and transfers a pre-defined percentage to the other parties who contributed to the miners. A log file is created after the script is finished disbursing.

### How to use:
This script can for example run every day using a cronjob on the same machine that runs the wallet with the escrow account, or can be used by implementing it in a web page (like G-DASH) to disburse the earnings manually.
