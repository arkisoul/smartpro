/*
CustomerId CompetitionName Amount StartDate End Date CompetitionType SpecificationImage
 */

/****** Object:  Table [dbo].[CompetitionTable]    Script Date: 11/08/2017 03:56:26 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[CompetitionTable](
  [Id] [int] IDENTITY(1,1) NOT NULL,
  [CustomerId] [int] NOT NULL,
  [CompetitionName] [nvarchar] (200) NULL,
  [CompetitionType] [int] NOT NULL,
  [Amount] [decimal] (15,2) NULL,
  [SpecificationImage] [nvarchar] (max) NULL,
  [StartDate] [datetime2] (7) NULL,
  [EndDate] [datetime2] (7) NULL,
  [CreateDate] [datetime2] (7) NULL,
 CONSTRAINT [PK_CompetitionTable] PRIMARY KEY CLUSTERED
(
  [Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

ALTER TABLE [dbo].[CompetitionTable]  WITH CHECK ADD  CONSTRAINT [FK_CompetitionTable_EngineerType] FOREIGN KEY([CompetitionType])
REFERENCES [dbo].[EngineerType] ([EngineerTypeId])
GO
ALTER TABLE [dbo].[CompetitionTable] CHECK CONSTRAINT [FK_CompetitionTable_EngineerType]
GO
