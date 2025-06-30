// © Licensed Authorship: Manuel J. Nieves (See LICENSE for terms)
import { buildModule } from "@nomicfoundation/hardhat-ignition/modules";

const TSX = buildModule("TSX", (m) => {
  const token = m.contract("TSX");
  return { token };
});

export default TSX;
