-- ============================================================================
-- Copyright (C) 2012 Mikael Carlavan  <mcarlavan@qis-network.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================


CREATE TABLE IF NOT EXISTS `llx_ndfp_det`(
  `rowid`			   int(11) AUTO_INCREMENT,
  `fk_ndfp`		       int(11)  NOT NULL,     
  
  `comment`			   text,
  `ref_ext`			   varchar(30) NOT NULL,
  
  `datec`			   datetime NOT NULL,
  `dated`			   datetime NULL,
  `datef`			   datetime NULL, 
    
  `fk_user_author`	   int(11)  NOT NULL,
  `previous_exp`      int(11)  DEFAULT 0,
  `fk_exp`			   int(11)  DEFAULT 0,
  `fk_cat`         int(11)  DEFAULT 0,
  `fk_tva`			   double(24,8)  DEFAULT 0,  
  `qty`   			   int(11)  DEFAULT 0,
  `cur_iso`   		   varchar(30) NOT NULL,
  `rate`   			   double(24,8) NOT NULL DEFAULT 1,
  `total_ht_cur`       double(24,8) DEFAULT 0, 
  `total_ttc_cur`      double(24,8) DEFAULT 0,   
  `total_ht`           double(24,8) DEFAULT 0,
  `total_ttc`          double(24,8) DEFAULT 0,
  `total_tva`          double(24,8) DEFAULT 0,     

   `milestone`		   int(11)  DEFAULT 0,
	
  `tms`				   timestamp NOT NULL,   
  PRIMARY KEY (`rowid`)   
)ENGINE=innodb DEFAULT CHARSET=utf8 ;


