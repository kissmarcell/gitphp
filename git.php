<?php

class Git{
    private string $githubToken;
    private string $username;
    private string $repository;
    private string $branch;
    private string $author_email;
    private string $author_name;

    function __construct(string $githubToken, string $username, string $repository, string $branch, string $author_email, string $author_name)
    {
        $this->githubToken = $githubToken;
        $this->username = $username;
        $this->repository = $repository;
        $this->branch = $branch;
        $this->author_email = $author_email;
        $this->author_name = $author_name;
    }

    public function commit(string $message, array $files): string
    {
        $tree = [];

        foreach ($files as $filePath => $fileContent) {
            $tree[] = [
                'path' => $filePath,
                'mode' => '100644', // File mode
                'type' => 'blob',   // Type is blob for files
                'content' => $fileContent, // Encode content in base64
            ];
        }

        // Create a new tree
        $treeSha = $this->createTree($tree);

        // Get the latest commit SHA of the branch
        $latestCommitSha = $this->getLatestCommitSha();

        // Create a new commit
        $commitData = [
            'message' => $message,
            'author' => [
                'name' => $this->author_name,
                'email' => $this->author_email,
            ],
            'parents' => [$latestCommitSha], // Use the latest commit of the branch
            'tree' => $treeSha,
        ];

        $commitSha = $this->createCommit($commitData);

        // Update the branch reference to point to the new commit
        $this->updateReference($commitSha);

        return $commitSha;
    }

    private function getLatestCommitSha(){
        // Retrieve the latest commit SHA for the branch
        $url = "https://api.github.com/repos/$this->username/$this->repository/git/refs/heads/$this->branch";
        $headers = [
            "Authorization: token $this->githubToken",
            "User-Agent: PHP"
        ];

        $response = $this->curl('GET', $url, $headers);

        $refData = json_decode($response, true);
        return $refData['object']['sha'];
    }

    private function createTree($tree){
        // Create a new tree
        $url = "https://api.github.com/repos/$this->username/$this->repository/git/trees";
        $headers = [
            "Authorization: token $this->githubToken",
            "User-Agent: PHP"
        ];

        $treeData = [
            'base_tree' => $this->getLatestCommitSha(),
            'tree' => $tree,
        ];

        $response = $this->curl('POST', $url, $headers, $treeData);

        $treeInfo = json_decode($response, true);
        return $treeInfo['sha'];
    }

    private function createCommit($commitData)
    {
        // Create a new commit
        $url = "https://api.github.com/repos/$this->username/$this->repository/git/commits";
        $headers = [
            "Authorization: token $this->githubToken",
            "User-Agent: PHP"
        ];

        $response = $this->curl('POST', $url, $headers, $commitData);

        $commitInfo = json_decode($response, true);
        return $commitInfo['sha'];
    }

    private function updateReference($commitSha): void
    {
        // Update the reference (branch) to point to the new commit
        $url = "https://api.github.com/repos/$this->username/$this->repository/git/refs/heads/$this->branch";
        $headers = [
            "Authorization: token $this->githubToken",
            "User-Agent: PHP"
        ];

        $data = [
            'sha' => $commitSha,
            'force' => true, // Force update
        ];

        $response = $this->curl('PATCH', $url, $headers, $data);

        if ($response) {
            echo 'Branch reference updated successfully.';
        } else {
            echo 'Error updating branch reference.';
        }
    }

    private function curl(string $method, string $url, array $headers, array $data = []): bool|string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Set HTTP method-specific options
        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            default:
                // Unsupported HTTP method
                throw new InvalidArgumentException("Unsupported HTTP method: $method");
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}