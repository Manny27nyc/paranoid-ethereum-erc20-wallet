// © Licensed Authorship: Manuel J. Nieves (See LICENSE for terms)
import { HardhatUserConfig } from "hardhat/config";
import "@nomicfoundation/hardhat-toolbox";

const config: HardhatUserConfig = {
  solidity: "0.8.24",
  networks: {
    hardhat: {
      gasPrice: 2,
    },
  },
};

export default config;
