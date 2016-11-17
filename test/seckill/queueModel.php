<?php
/**
 * Created by PhpStorm.
 * User: rita
 * Date: 2016/11/17
 * Time: 8:44
 * ͨ��redisʵ���������
 */
class queueModel
{
    private $redis;//redis����
    private $lockModel;

    public function __construct($redis)
    {
        date_default_timezone_set('Asia/Shanghai');

        $this->redis = $redis;

        //����ֲ�ʽ����ģ��
        require './lockModel.php';
        $this->lockModel = new lockModel($this->redis);
    }

    /**
     * ���
     * @param $name ��������
     * @param $user_id ��ԱID
     * @param $timeout ��ӳ�ʱʱ��
     * @param $afterInterVal
     * @return boolean
    **/
    public function enqueue($name, $user_id, $timeout=10, $afterInterVal=0)
    {
        //�Ϸ��Լ��
        if(empty($name) || empty($user_id)) {
            return 'ȱ�ٲ���';
        }

        //����
        if(!$this->lockModel->lock("Queue:{$name}", $timeout)) {
            return '����ʧ��';
        }

        //�ж϶����Ƿ񳬹�ָ���ļ���Ԫ��,�ݲ���10���û�
        $count = $this->redis->zCard("Queue:{$name}");
        if($count >=  $this->redis->get("goods:{$name}:stock")) {
            $this->lockModel->unlock("Queue:$name");
            return '����ָ����������';
        }

        //���ʱ�Ե�ǰʱ�����Ϊscore
        $score = microtime(true) + $afterInterVal;

        //���
        //���ж����Ƿ��Ѿ����ڸ�id��
        if (false === $this->redis->zScore("Queue:$name", $user_id)) {
            $this->redis->zAdd("Queue:$name", $score, $user_id);
        }

        //����
        $this->lockModel->unlock("Queue:$name");

        return '��ӳɹ�';
    }

    /**
     * ����һ��Task����Ҫָ��$user_id �� $score
     * ���$score ������е�ƥ������ӣ�������Ϊ��Task�ѱ�������ӹ�����ǰ������ʧ�ܴ���
     *
     * @param  [type]  $name    ��������
     * @param  [type]  $user_id   ��ԱID
     * @param  [type]  $score   �����Ӧscore���Ӷ����л�ȡ����ʱ�᷵��һ��score��ֻ��$score�Ͷ����е�ֵƥ��ʱTask�Żᱻ����
     * @param  integer $timeout ��ʱʱ��(��)
     * @return [type]           Task�Ƿ�ɹ�������false������redis����ʧ�ܣ�Ҳ�п�����$score������е�ֵ��ƥ�䣨���ʾ��Task�Դӻ�ȡ������֮�������߳���ӹ���
     */
    public function deQueue($name, $user_id, $score, $timeout = 10) {
        //�Ϸ��Լ��
        if (empty($name) || empty($user_id) || empty($score)) return false;

        //����
        if(!$this->lockModel->lock("Queue:{$name}", $timeout)) {
            return false;
        }

        //����
        //��ȡ��redis��score
        $serverScore = $this->redis->zScore("Queue:$name", $user_id);
        $result = false;
        //���жϴ�������score��redis��score�Ƿ���һ��
        if ($serverScore == $score) {
            //ɾ����$id
            $result = (float)$this->redis->zDelete("Queue:$name", $user_id);
            if ($result == false) {
                return false;
            }
        }
        //����
        $this->lockModel->unlock("Queue:$name");

        return $result;
    }

    /**
     * ��ȡ���ж������ɸ�Task ���������
     * @param  [type]  $name    ��������
     * @param  integer $count   ����
     * @param  integer $timeout ��ʱʱ��
     * @return [type]     ��������
     */
    public function pop($name, $count = 1, $timeout = 10) {
        //�Ϸ��Լ��
        if (empty($name) || $count <= 0) return [];

        //����
        require './lockModel.php';
        $lockModel = new lockModel();
        if(!$lockModel->lock("Queue:{$name}", $timeout)) {
            return false;
        }

        //ȡ�����ɵ�Task
        $result = [];
        $array = $this->redis->zByScore("Queue:$name", false, microtime(true), true, false, [0, $count]);

        //�������$result������ �� ɾ����redis��Ӧ��id
        foreach ($array as $id => $score) {
            $result[] = ['id' => $id, 'score' => $score];
                $this->redis->zDelete("Queue:$name", $id);
        }

        //����
        $lockModel->unlock("Queue:$name");

        return $count == 1 ? (empty($result) ? false : $result[0]) : $result;
    }

    /**
     * ��ȡ���ж��������ɸ�Task
     * @param  [type]  $name  ��������
     * @param  integer $count ����
     * @return [type]   ��������
     */
    public function top($name, $count = 1) {
        //�Ϸ��Լ��
        if (empty($name) || $count < 1)  return [];

        //ȡ�����ɸ�Task
        $result = [];
        $array = $this->redis->getByScore("Queue:$name", false, microtime(true), true, false, [0, $count]);

        //��Task�����������
        foreach ($array as $id => $score) {
            $result[] = ['id' => $id, 'score' => $score];
        }

        //��������
        return $count == 1 ? (empty($result) ? false : $result[0]) : $result;
    }
}