// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

/**
 * Minimal permissioned anchor contract for OrgChain.
 *
 * Application data stays in MySQL. Only tamper-evident SHA-256 record hashes
 * and a small reference hash are written to Besu. This keeps the chain free of
 * personal voter data and makes the contract suitable for a local thesis/demo
 * network.
 */
contract OrgChainAnchor {
    address public immutable submitter;

    mapping(bytes32 => bool) private anchored;

    event RecordAnchored(
        bytes32 indexed recordHash,
        bytes32 indexed referenceHash,
        uint8 indexed recordType,
        uint256 anchoredAt,
        address submitter
    );

    constructor() {
        submitter = msg.sender;
    }

    function anchor(bytes32 recordHash, bytes32 referenceHash, uint8 recordType) external {
        require(msg.sender == submitter, "ORGCHAIN: unauthorized submitter");
        require(recordHash != bytes32(0), "ORGCHAIN: empty record hash");
        require(!anchored[recordHash], "ORGCHAIN: record already anchored");

        anchored[recordHash] = true;
        emit RecordAnchored(recordHash, referenceHash, recordType, block.timestamp, msg.sender);
    }

    function isAnchored(bytes32 recordHash) external view returns (bool) {
        return anchored[recordHash];
    }
}

