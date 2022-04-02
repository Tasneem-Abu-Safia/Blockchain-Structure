<?php
class Block{
    private $index; //integer
    private $timestamp; //integer
    private $transaction; //array transaction
    private $previousHash ;
    private $proof; //int (work proof algotithm)
    private $hash;
    private $difficulty;
    private $merkle_root;
    private $version;
    public function getHash()
    {
        return $this->hash;
    }
    public function __construct($index,$timestamp,$transaction,$previousHash ,$proof,$difficulty,$merkle_root,$version)
    {
        $this->index = $index;
        $this->timestamp = $timestamp;
        $this->transaction = $transaction;
        $this->previousHash = $previousHash ;
        $this->proof = $proof;
        $this->difficulty = '0000';
        $this->version = $version;
        $this->merkle_root =  $merkle_root;
        $this->hash = $this->BlockHash();
    }
    private function BlockHash()
    {
        $array = [
          'index' =>  $this->index ,
          'timestamp' => $this->timestamp ,
          'transaction' => $this->transaction ,
          'previousHash' => $this->previousHash ,
           'proof' => $this->proof ,
            'difficulty' => $this->difficulty ,
            'version' => $this->version,
            'merkle_root' => $this->merkle_root
        ];
        $stringBlock = json_encode($array);
        return hash('sha256' , $stringBlock);
    }
}
class MerkleTree
{
    private $element_list;
    private $root;
    public function __construct()
    {
        $this->element_list = array();
        $this->root         = "";
    }
    /**
     * Add an element to the Merkle Tree.  This method automatically generates the
     * hash of the element and adds it as a leaf of the Merkle tree.
     *
     * @param Mixed $element
     * @return void
     */
    public function addElement($element)
    {
        $this->element_list[] = $this->hash($element);
    }
    /**
     * Loop through all the created leaves and start building the tree.
     *
     * @return void
     */
    public function create()
    {
        $new_list = $this->element_list;
        // This is simply "going up one level".
        while (count($new_list) != 1) {
            $new_list = $this->getNewList($new_list);
        }
        $this->root = $new_list[0];
        // We return the root immediately, but there is also a getRoot() method.
        return $this->root;
    }
    /**
     * This method creates the parent level of the current nodes (or leaves).
     * If there is no right sibling, then the left element is re-used.
     *
     * @param Array $temp_list
     * @return void
     */
    private function getNewList($temp_list)
    {
        $i        = 0;
        $new_list = array();
        while ($i < count($temp_list)) {
            // Left child
            $left = $temp_list[$i];
            $i++;
            // Right child
            if ($i != count($temp_list)) {
                $right = $temp_list[$i];
            } else {
                $right = $left;
            }
            // Hash and add as parent.
            $hash_value = $this->hash($left . $right);
            $new_list[] = $hash_value;
            $i++;
        }
       return $new_list;
    }
    /**
     * The hash function is pretty simple.  Change this to your convenience.
     * i.e. Bitcoin uses double sha256 hashing.
     *
     * @return String hash  The hashed result
     */
    private function hash($string)
    {
        // Bitcoin's method
        // return hash('sha256', hash('sha256', $string, false), false);
        return hash('sha256', $string);
    }
    /**
     * The root is already calculated when creating the tree.  Here we just return it.
     *
     * @return String  The Merkle Root
     */
    public function getRoot()
    {
        return $this->root;
    }
}
class BlockChain{
    private $chain;
    private $currentTransaction;
    private $MerkleTree ;
    public function __construct()
    {
        $this->chain = [$this->GenesisBlock()];
        $this->currentTransaction =[];
        $this->MerkleTree = new MerkleTree();
    }
    private function GenesisBlock()
    {
        $block0= [
            'index' =>  1 ,
            'timestamp' => time() ,
            'transaction' => [],
            'previousHash' => '0000000000000000000000000000000000000000000000000000000000000000000000000000',
            'proof' => 100 ,
            'difficulty' => '0000',
            'version' => '0x1',
            'merkle_root' => '4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b'
        ];
        $block0['hash'] = (new Block($block0['index'],$block0['timestamp'],
            $block0['transaction'],$block0['previousHash'],$block0['proof'],$block0['difficulty'],$block0['merkle_root'],$block0['version']))->getHash();
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
        $this->currentTransaction[] = $Transaction;
        $this->MerkleTree->addElement(json_encode($Transaction));
        return true;
    }
    public function addBlock($proof)
    {
        $PreviousBlockInfo = $this->chain[count($this->chain)-1];
        if($this->checkProof($proof,$PreviousBlockInfo['proof']) == false){
            return false;
        }
        $this->MerkleTree->create();
        //Todo rewards miners (in transaction)
        $block = [
            'index'        => count($this->chain) + 1,
            'timestamp'    => time(),
            'transactions' => $this->currentTransaction,
            'proof'        => $proof,
            'previous_hash' => $PreviousBlockInfo['hash'],
            'difficulty' => '0000',
            'version' => '0x1',
            'merkle_root' =>  $this->MerkleTree->getRoot()
        ];
        $block['hash'] = (new Block($block['index'],$block['timestamp'],$block['transactions'],$block['previous_hash'],$block['proof'],$block['difficulty'],$block['merkle_root'],$block['version']))->getHash();
        //add New block
        $this->chain[] = $block;
        $string = json_encode($block,JSON_PRETTY_PRINT);
        $myfile = fopen("Central_BlockChain.txt", "a+") or die("Unable to open file!");
        fwrite($myfile,$string);
        fclose($myfile);
        //Reset transaction
        $this->currentTransaction = [];
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
