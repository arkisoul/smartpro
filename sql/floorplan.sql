/****** Object:  Table [dbo].[FloorType]    Script Date: 11/30/2017 01:19:02 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[FloorType](
  [Id] [int] IDENTITY(1,1) NOT NULL,
  [Name] [nvarchar] (200) NULL,
  [Description] [nvarchar] (max) NULL,
 CONSTRAINT [PK_FloorType] PRIMARY KEY CLUSTERED
(
  [Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

/****** Object:  Table [dbo].[BuildingMaterialItems]    Script Date: 11/30/2017 01:19:02 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[BuildingMaterialItems](
  [Id] [int] IDENTITY(1,1) NOT NULL,
  [Name] [nvarchar] (200) NULL,
  [Description] [nvarchar] (max) NULL,
  [Price] [decimal] (20,4) NULL,
 CONSTRAINT [PK_BuildingMaterialItems] PRIMARY KEY CLUSTERED
(
  [Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

/****** Object:  Table [dbo].[BuildingMaterialRequirement]    Script Date: 11/30/2017 01:19:02 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[BuildingMaterialRequirement](
  [Id] [int] IDENTITY(1,1) NOT NULL,
  [FloorTypeId] [int] NOT NULL,
  [BuildingMaterialItemId] [int] NOT NULL,
  [Qty] [decimal] (22,6) NULL,
  [Percentage] [decimal] (6,2) NULL,
 CONSTRAINT [PK_BuildingMaterialRequirement] PRIMARY KEY CLUSTERED
(
  [Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

ALTER TABLE [dbo].[BuildingMaterialRequirement]  WITH CHECK ADD  CONSTRAINT [FK_BuildingMaterialRequirement_FloorType] FOREIGN KEY([FloorTypeId])
REFERENCES [dbo].[FloorType] ([Id])
GO
ALTER TABLE [dbo].[BuildingMaterialRequirement] CHECK CONSTRAINT [FK_BuildingMaterialRequirement_FloorType]
GO

ALTER TABLE [dbo].[BuildingMaterialRequirement]  WITH CHECK ADD  CONSTRAINT [FK_BuildingMaterialRequirement_BuildingMaterialItems] FOREIGN KEY([BuildingMaterialItemId])
REFERENCES [dbo].[BuildingMaterialItems] ([Id])
GO
ALTER TABLE [dbo].[BuildingMaterialRequirement] CHECK CONSTRAINT [FK_BuildingMaterialRequirement_BuildingMaterialItems]
GO

/****** Object:  Table [dbo].[FloorPlanRequest]    Script Date: 11/30/2017 01:19:02 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[FloorPlanRequest](
  [Id] [int] IDENTITY(1,1) NOT NULL,
  [CustomerId] [int] NOT NULL,
  [FloorTypeId] [int] NOT NULL,
  [RequestedDate] [datetime2] (7) NULL,
 CONSTRAINT [PK_FloorPlanRequest] PRIMARY KEY CLUSTERED
(
  [Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

/****** Object:  Table [dbo].[FloorPlanRequestDetail]    Script Date: 11/30/2017 01:19:02 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE [dbo].[FloorPlanRequestDetail](
  [Id] [int] IDENTITY(1,1) NOT NULL,
  [FloorPlanRequestId] [int] NOT NULL,
  [BuildingMaterialItem] [nvarchar] (200) NULL,
  [Qty] [decimal] (22,6) NULL,
  [Price] [decimal] (20,4) NULL,
 CONSTRAINT [PK_FloorPlanRequestDetail] PRIMARY KEY CLUSTERED
(
  [Id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

ALTER TABLE [dbo].[FloorPlanRequestDetail]  WITH CHECK ADD  CONSTRAINT [FK_FloorPlanRequestDetail_FloorPlanRequest] FOREIGN KEY([FloorPlanRequestId])
REFERENCES [dbo].[FloorPlanRequest] ([Id])
GO
ALTER TABLE [dbo].[FloorPlanRequestDetail] CHECK CONSTRAINT [FK_FloorPlanRequestDetail_FloorPlanRequest]
GO
