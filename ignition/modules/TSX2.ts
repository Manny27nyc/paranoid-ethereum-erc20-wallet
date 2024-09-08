import { buildModule } from "@nomicfoundation/hardhat-ignition/modules";

const TSX2 = buildModule("TSX2", (m) => {
  const token = m.contract("TSX2");
  return { token };
});

export default TSX2;
