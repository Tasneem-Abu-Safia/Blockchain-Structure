<?php
class Block{
    private $index; //integer
    private $timestamp; //integer
    private $transaction; //array transaction
    private $previousHash ;
    private $proof; //int (work proof algotithm)
    private $hash;

    public function getHash()
    {
        return $this->hash;
    }

    public function __construct($index,$timestamp,$transaction,$previousHash ,$proof)
    {
        $this->index = $index;
        $this->timestamp = $timestamp;
        $this->transaction = $transaction;
        $this->previousHash = $previousHash ;
        $this->proof = $proof;
        $this->hash = $this->BlockHash();

    }

    private function BlockHash()
    {
        $array = [
          'index' =>  $this->index ,
          'timestamp' => $this->timestamp ,
          'transaction' => $this->transaction ,
          'previousHash' => $this->previousHash ,
           'proof' => $this->proof

        ];
        $stringBlock = json_encode($array);
        return hash('sha256' , $stringBlock);
    }
}
class BlockChain{
    private $chain;
    private $currentTransaction;

    public function __construct()
    {
        $this->chain = [$this->GenesisBlock()];
        $this->currentTransaction =[];
    }

    private function GenesisBlock()
    {
        $block0= [
            'index' =>  1 ,
            'timestamp' => time() ,
            'transaction' => [],
            'previousHash' => '0000000000000000000000000000000000000000000000000000000000000000000000000000',
            'proof' => 100 ,

        ];
        $block0['hash'] = (new Block($block0['index'],$block0['timestamp'],
            $block0['transaction'],$block0['previousHash'],$block0['proof']))->getHash();
        $string = json_encode($block0,JSON_PRETTY_PRINT);
        $myfile = fopen("Central_BlockChain.txt", "a+") or die("Unable to open file!");
        fwrite($myfile,$string);
        fclose($myfile);
       return $block0;
    }

    public function createTransaction($senderPrivateKey,$senderAddress,$recipientAddress,$amount)
    {
        $Transaction = [
            'from'   => $senderAddress,
            'to'     => $recipientAddress,
            'amount' => $amount,
            'timestamp' => time()
        ];
       $this->currentTransactions[] = $Transaction;
        return true;
    }
    public function addBlock($proof)
    {

        $PreviousBlockInfo = $this->chain[count($this->chain)-1];
        if($this->checkProof($proof,$PreviousBlockInfo['proof']) == false){
            return false;
        }
        //Todo rewards miners (in transaction)
        $block = [
            'index'        => count($this->chain) + 1,
            'timestamp'    => time(),
            'transactions' => $this->currentTransactions,
            'proof'        => $proof,
            'previous_hash' => $PreviousBlockInfo['hash'],

        ];
        $block['hash'] = (new Block($block['index'],$block['timestamp'],$block['transactions'],$block['previous_hash'],$block['proof']))->getHash();
        //add New block
        $this->chain[] = $block;

        $string = json_encode($block,JSON_PRETTY_PRINT);
        $myfile = fopen("Central_BlockChain.txt", "a+") or die("Unable to open file!");
        fwrite($myfile,$string);
        fclose($myfile);

        //Reset transaction
        $this->currentTransactions = [];
        return true;
    }

    private function checkProof($proof,$preProof)
    {
        $string = $proof.$preProof;
        $hash   = hash('sha256',$string);
        if(substr($hash,0,4) == '0000'){
            return true;
        }else{
            return false;
        }
    }
    public function mine()
    {
        $proof = 0;
        //Latest block
        $blockInfo = $this->chain[count($this->chain)-1];
        $preProof  = $blockInfo['proof'];
        while (true)
        {
            $string = $proof.$preProof;
            $hash   = hash('sha256',$string);
            if(substr($hash,0,4) == '0000'){
                //Add new block
                $this->addBlock($proof);
                break;
            }
            $proof++;
        }

       }
}
$BlockChain = new BlockChain();
$BlockChain->createTransaction('','bc1qx4dsxn8lcjvy2lwnrjd2l63dwaz7n6pfgswtpp','bc1qv9algnn8f3n0seeldhx5e7wfqd3e9c2xs7p5d0',1);
$BlockChain->mine();

$BlockChain->createTransaction('','bc1qqlgcwp9l0ptcg2qhaccejexxc8yqm9vyahzmyw','1FZHyNwk5EQ4d6JUMsrLpVunSpuENqARhe',1);
$BlockChain->mine();

?>